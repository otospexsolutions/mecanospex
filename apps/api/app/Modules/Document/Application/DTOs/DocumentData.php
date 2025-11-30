<?php

declare(strict_types=1);

namespace App\Modules\Document\Application\DTOs;

use App\Modules\Document\Domain\Document;
use Spatie\LaravelData\Data;

final class DocumentData extends Data
{
    /**
     * @param  list<DocumentLineData>  $lines
     */
    public function __construct(
        public string $id,
        public string $tenant_id,
        public string $partner_id,
        public ?string $vehicle_id,
        public string $type,
        public string $status,
        public string $document_number,
        public string $document_date,
        public ?string $due_date,
        public ?string $valid_until,
        public string $currency,
        public ?string $subtotal,
        public ?string $discount_amount,
        public ?string $tax_amount,
        public ?string $total,
        public ?string $notes,
        public ?string $internal_notes,
        public ?string $reference,
        public ?string $source_document_id,
        public array $lines,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Document $document, bool $includeLines = true): self
    {
        $lines = [];
        if ($includeLines) {
            foreach ($document->lines as $line) {
                $lines[] = DocumentLineData::fromModel($line);
            }
        }

        return new self(
            id: $document->id,
            tenant_id: $document->tenant_id,
            partner_id: $document->partner_id,
            vehicle_id: $document->vehicle_id,
            type: $document->type->value,
            status: $document->status->value,
            document_number: $document->document_number,
            document_date: $document->document_date->toDateString(),
            due_date: $document->due_date?->toDateString(),
            valid_until: $document->valid_until?->toDateString(),
            currency: $document->currency,
            subtotal: $document->subtotal !== null ? number_format((float) $document->subtotal, 2, '.', '') : null,
            discount_amount: $document->discount_amount !== null ? number_format((float) $document->discount_amount, 2, '.', '') : null,
            tax_amount: $document->tax_amount !== null ? number_format((float) $document->tax_amount, 2, '.', '') : null,
            total: $document->total !== null ? number_format((float) $document->total, 2, '.', '') : null,
            notes: $document->notes,
            internal_notes: $document->internal_notes,
            reference: $document->reference,
            source_document_id: $document->source_document_id,
            lines: $lines,
            created_at: $document->created_at?->toIso8601String() ?? '',
            updated_at: $document->updated_at?->toIso8601String() ?? '',
        );
    }
}
