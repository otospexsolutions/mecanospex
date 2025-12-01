<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Services;

use App\Modules\Document\Domain\DocumentSequence;
use App\Modules\Document\Domain\Enums\DocumentType;
use Illuminate\Support\Facades\DB;

class DocumentNumberingService
{
    /**
     * Generate the next document number for a company and type
     *
     * Format: PREFIX-YYYY-NNNN (e.g., INV-2025-0001)
     */
    public function generateNumber(string $tenantId, string $companyId, DocumentType $type): string
    {
        return DB::transaction(function () use ($tenantId, $companyId, $type): string {
            $year = (int) date('Y');
            $prefix = $type->getPrefix();

            // Lock the sequence row for update
            $sequence = DocumentSequence::where('company_id', $companyId)
                ->where('type', $type->value)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                // Create new sequence for this company/type/year
                $sequence = DocumentSequence::create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'type' => $type->value,
                    'year' => $year,
                    'last_number' => 0,
                ]);
            }

            // Increment and save
            $nextNumber = $sequence->last_number + 1;
            $sequence->update(['last_number' => $nextNumber]);

            // Format: PREFIX-YYYY-NNNN
            return sprintf('%s-%d-%04d', $prefix, $year, $nextNumber);
        });
    }

    /**
     * Get the current sequence number without incrementing
     */
    public function getCurrentNumber(string $companyId, DocumentType $type): int
    {
        $year = (int) date('Y');

        $sequence = DocumentSequence::where('company_id', $companyId)
            ->where('type', $type->value)
            ->where('year', $year)
            ->first();

        return $sequence !== null ? $sequence->last_number : 0;
    }
}
