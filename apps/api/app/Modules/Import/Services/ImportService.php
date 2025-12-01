<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Company\Services\CompanyContext;
use App\Modules\Import\Domain\Enums\ImportStatus;
use App\Modules\Import\Domain\Enums\ImportType;
use App\Modules\Import\Domain\ImportJob;
use App\Modules\Import\Domain\ImportRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ImportService
{
    public function __construct(
        private readonly ValidationEngine $validationEngine,
        private readonly CompanyContext $companyContext
    ) {}

    /**
     * Create a new import job
     *
     * @param  array<string, string>|null  $columnMapping
     */
    public function createJob(
        string $tenantId,
        string $userId,
        ImportType $type,
        string $filename,
        string $filePath,
        int $totalRows,
        ?array $columnMapping = null
    ): ImportJob {
        return ImportJob::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'status' => ImportStatus::Pending,
            'original_filename' => $filename,
            'file_path' => $filePath,
            'total_rows' => $totalRows,
            'column_mapping' => $columnMapping,
        ]);
    }

    /**
     * Add a row to an import job
     *
     * @param  array<string, mixed>  $data
     */
    public function addRow(ImportJob $job, int $rowNumber, array $data): ImportRow
    {
        return ImportRow::create([
            'import_job_id' => $job->id,
            'row_number' => $rowNumber,
            'data' => $data,
            'is_valid' => false,
        ]);
    }

    /**
     * Validate all rows in an import job
     */
    public function validateJob(ImportJob $job): void
    {
        $job->update(['status' => ImportStatus::Validating]);

        $rules = $job->type->getValidationRules();
        $validCount = 0;
        $invalidCount = 0;

        foreach ($job->rows as $row) {
            $result = $this->validationEngine->validate(
                $row->data,
                $rules,
                $job->tenant_id
            );

            $row->update([
                'is_valid' => $result['is_valid'],
                'errors' => $result['errors'],
            ]);

            if ($result['is_valid']) {
                $validCount++;
            } else {
                $invalidCount++;
            }
        }

        $job->update([
            'status' => ImportStatus::Validated,
            'successful_rows' => $validCount,
            'failed_rows' => $invalidCount,
        ]);
    }

    /**
     * Get failed rows for an import job
     *
     * @return Collection<int, ImportRow>
     */
    public function getFailedRows(ImportJob $job): Collection
    {
        return $job->rows()
            ->where('is_valid', false)
            ->orderBy('row_number')
            ->get();
    }

    /**
     * Get valid rows for an import job
     *
     * @return Collection<int, ImportRow>
     */
    public function getValidRows(ImportJob $job): Collection
    {
        return $job->rows()
            ->where('is_valid', true)
            ->orderBy('row_number')
            ->get();
    }

    /**
     * Execute import for valid rows
     */
    public function executeImport(ImportJob $job): void
    {
        if (! $job->canStart()) {
            throw new \RuntimeException('Import cannot be started. Fix validation errors first.');
        }

        $job->update([
            'status' => ImportStatus::Importing,
            'started_at' => now(),
        ]);

        $validRows = $this->getValidRows($job);
        $processedCount = 0;
        $successCount = 0;
        $failCount = 0;

        foreach ($validRows as $row) {
            try {
                DB::transaction(function () use ($job, $row, &$successCount): void {
                    $entityId = $this->importRow($job, $row);
                    $row->update([
                        'is_imported' => true,
                        'imported_entity_id' => $entityId,
                    ]);
                    $successCount++;
                });
            } catch (\Throwable $e) {
                $row->update([
                    'is_imported' => false,
                    'import_error' => $e->getMessage(),
                ]);
                $failCount++;
            }

            $processedCount++;
            $job->update(['processed_rows' => $processedCount]);
        }

        $job->update([
            'status' => $failCount > 0 ? ImportStatus::Failed : ImportStatus::Completed,
            'successful_rows' => $successCount,
            'failed_rows' => $failCount,
            'completed_at' => now(),
        ]);
    }

    /**
     * Import a single row
     */
    private function importRow(ImportJob $job, ImportRow $row): string
    {
        return match ($job->type) {
            ImportType::Partners => $this->importPartner($job->tenant_id, $row->data),
            ImportType::Products => $this->importProduct($job->tenant_id, $row->data),
            ImportType::StockLevels => $this->importStockLevel($job->tenant_id, $row->data),
            ImportType::OpeningBalances => $this->importOpeningBalance($job->tenant_id, $row->data),
        };
    }

    /**
     * Import a partner row
     *
     * @param  array<string, mixed>  $data
     */
    private function importPartner(string $tenantId, array $data): string
    {
        $partner = \App\Modules\Partner\Domain\Partner::create([
            'tenant_id' => $tenantId,
            'company_id' => $this->companyContext->getCompanyId(),
            'name' => $data['name'],
            'type' => \App\Modules\Partner\Domain\Enums\PartnerType::from($data['type']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
        ]);

        return $partner->id;
    }

    /**
     * Import a product row
     *
     * @param  array<string, mixed>  $data
     */
    private function importProduct(string $tenantId, array $data): string
    {
        $product = \App\Modules\Product\Domain\Product::create([
            'tenant_id' => $tenantId,
            'company_id' => $this->companyContext->getCompanyId(),
            'name' => $data['name'],
            'sku' => $data['sku'],
            'type' => \App\Modules\Product\Domain\Enums\ProductType::from($data['type']),
            'description' => $data['description'] ?? null,
            'sale_price' => $data['sale_price'] ?? null,
            'purchase_price' => $data['purchase_price'] ?? null,
            'barcode' => $data['barcode'] ?? null,
        ]);

        return $product->id;
    }

    /**
     * Import a stock level row
     *
     * @param  array<string, mixed>  $data
     */
    private function importStockLevel(string $tenantId, array $data): string
    {
        $companyId = $this->companyContext->getCompanyId();

        // Find product by SKU
        $product = \App\Modules\Product\Domain\Product::where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('sku', $data['product_sku'])
            ->firstOrFail();

        // Find location by code
        $location = \App\Modules\Company\Domain\Location::where('company_id', $companyId)
            ->where('code', $data['location_code'])
            ->firstOrFail();

        $stockLevel = \App\Modules\Inventory\Domain\StockLevel::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'product_id' => $product->id,
                'location_id' => $location->id,
            ],
            [
                'quantity' => $data['quantity'],
                'reserved' => 0,
            ]
        );

        return $stockLevel->id;
    }

    /**
     * Import an opening balance row
     *
     * @param  array<string, mixed>  $data
     */
    private function importOpeningBalance(string $tenantId, array $data): string
    {
        $companyId = $this->companyContext->getCompanyId();

        // Find account by code
        $account = \App\Modules\Accounting\Domain\Account::where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('code', $data['account_code'])
            ->firstOrFail();

        /** @var string $description */
        $description = $data['description'] ?? 'Opening Balance';
        /** @var string $reference */
        $reference = $data['reference'] ?? '';

        // Create journal entry for opening balance
        $entry = \App\Modules\Accounting\Domain\JournalEntry::create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'entry_number' => 'OB-'.now()->format('YmdHis').'-'.random_int(1000, 9999),
            'entry_date' => now(),
            'description' => $description.($reference !== '' ? ' - '.$reference : ''),
            'status' => \App\Modules\Accounting\Domain\Enums\JournalEntryStatus::Posted,
        ]);

        /** @var string $debit */
        $debit = $data['debit'] ?? '0.00';
        /** @var string $credit */
        $credit = $data['credit'] ?? '0.00';

        // Create journal line
        \App\Modules\Accounting\Domain\JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description,
        ]);

        return $entry->id;
    }

    /**
     * Parse CSV file and create rows
     *
     * @return array{headers: array<string>, row_count: int}
     */
    public function parseCsvFile(ImportJob $job, string $content): array
    {
        $lines = explode("\n", trim($content));
        /** @var string $headerLine */
        $headerLine = array_shift($lines);
        $rawHeaders = str_getcsv($headerLine);
        /** @var array<string> $headers */
        $headers = array_map(fn (?string $h) => strtolower(trim($h ?? '')), $rawHeaders);

        $rowNumber = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $rowNumber++;
            $values = str_getcsv($line);
            /** @var array<string, mixed> $data */
            $data = array_combine($headers, $values);

            $this->addRow($job, $rowNumber, $data);
        }

        return [
            'headers' => $headers,
            'row_count' => $rowNumber,
        ];
    }
}
