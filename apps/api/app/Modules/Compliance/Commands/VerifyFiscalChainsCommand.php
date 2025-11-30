<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Commands;

use App\Modules\Compliance\Services\FiscalHashService;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Console\Command;

class VerifyFiscalChainsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fiscal:verify-chains
                            {--tenant= : Specific tenant ID to verify}
                            {--type= : Document type to verify (invoice, credit_note)}
                            {--fix : Attempt to fix broken chains (dangerous)}';

    /**
     * @var string
     */
    protected $description = 'Verify the integrity of fiscal hash chains';

    public function __construct(
        private readonly FiscalHashService $hashService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting fiscal chain verification...');
        $this->newLine();

        $tenantId = $this->option('tenant');
        $documentType = $this->option('type');

        /** @var \Illuminate\Database\Eloquent\Collection<int, Tenant> $tenants */
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');

            return Command::FAILURE;
        }

        $overallValid = true;
        $totalDocuments = 0;
        $invalidChains = 0;

        foreach ($tenants as $tenant) {
            /** @var Tenant $tenant */
            $this->info("Verifying tenant: {$tenant->name} ({$tenant->id})");

            /** @var string|null $typeOption */
            $typeOption = $documentType;
            $types = $typeOption
                ? [DocumentType::from($typeOption)]
                : [DocumentType::Invoice, DocumentType::CreditNote];

            foreach ($types as $type) {
                $result = $this->verifyChainForTenantAndType($tenant->id, $type);

                $totalDocuments += $result['count'];

                if (! $result['valid']) {
                    $overallValid = false;
                    $invalidChains++;
                    $this->error("  ✗ {$type->value}: INVALID at sequence {$result['failed_at']}");

                    if ($result['details']) {
                        $this->warn("    → {$result['details']}");
                    }
                } else {
                    $this->info("  ✓ {$type->value}: Valid ({$result['count']} documents)");
                }
            }

            $this->newLine();
        }

        $this->newLine();
        $this->info('Verification Summary:');
        $this->info("  Total documents verified: {$totalDocuments}");

        if ($overallValid) {
            $this->info('  Status: ALL CHAINS VALID ✓');

            return Command::SUCCESS;
        }

        $this->error("  Status: {$invalidChains} CHAIN(S) INVALID ✗");

        return Command::FAILURE;
    }

    /**
     * @return array{valid: bool, count: int, failed_at: int|null, details: string|null}
     */
    private function verifyChainForTenantAndType(string $tenantId, DocumentType $type): array
    {
        $documents = Document::where('tenant_id', $tenantId)
            ->where('type', $type)
            ->where('status', DocumentStatus::Posted)
            ->whereNotNull('fiscal_hash')
            ->orderBy('chain_sequence')
            ->get();

        if ($documents->isEmpty()) {
            return [
                'valid' => true,
                'count' => 0,
                'failed_at' => null,
                'details' => null,
            ];
        }

        $previousHash = null;

        foreach ($documents as $document) {
            // Check chain sequence continuity
            if ($document->previous_hash !== $previousHash) {
                return [
                    'valid' => false,
                    'count' => $documents->count(),
                    'failed_at' => $document->chain_sequence,
                    'details' => "Previous hash mismatch for {$document->document_number}",
                ];
            }

            // Verify the document's own hash
            $input = $this->hashService->serializeForHashing([
                'document_number' => $document->document_number,
                'posted_at' => $document->document_date->toDateString(),
                'total' => $document->total ?? '0.00',
                'currency' => $document->currency,
            ]);

            $storedHash = $document->fiscal_hash ?? '';
            if (! $this->hashService->verifyHash($input, $previousHash, $storedHash)) {
                return [
                    'valid' => false,
                    'count' => $documents->count(),
                    'failed_at' => $document->chain_sequence,
                    'details' => "Hash verification failed for {$document->document_number}",
                ];
            }

            $previousHash = $document->fiscal_hash;
        }

        return [
            'valid' => true,
            'count' => $documents->count(),
            'failed_at' => null,
            'details' => null,
        ];
    }
}
