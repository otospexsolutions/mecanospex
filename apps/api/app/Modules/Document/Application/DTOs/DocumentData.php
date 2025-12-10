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
        public ?string $partner_name,
        public ?string $partner_email,
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
        public ?string $balance_due,
        public ?string $amount_paid,
        public ?string $amount_residual,
        public ?string $notes,
        public ?string $internal_notes,
        public ?string $reference,
        public ?string $source_document_id,
        public ?string $converted_to_order_id,
        public ?string $converted_at,
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

        // Load partner if not already loaded
        $partner = $document->relationLoaded('partner') ? $document->partner : $document->partner()->first();

        // Extract conversion info from payload
        $payload = $document->payload ?? [];
        $convertedToOrderId = isset($payload['converted_to_order_id']) ? (string) $payload['converted_to_order_id'] : null;
        $convertedAt = isset($payload['converted_at']) ? (string) $payload['converted_at'] : null;

        // Calculate balance and amount paid
        $total = $document->total !== null ? (float) $document->total : 0.0;
        $balanceDue = $document->balance_due !== null ? (float) $document->balance_due : $total;
        $amountPaid = $total - $balanceDue;

        return new self(
            id: $document->id,
            tenant_id: $document->tenant_id,
            partner_id: $document->partner_id,
            partner_name: $partner?->name,
            partner_email: $partner?->email,
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
            total: $document->total !== null ? number_format($total, 2, '.', '') : null,
            balance_due: number_format($balanceDue, 2, '.', ''),
            amount_paid: number_format($amountPaid, 2, '.', ''),
            amount_residual: number_format($balanceDue, 2, '.', ''),
            notes: $document->notes,
            internal_notes: $document->internal_notes,
            reference: $document->reference,
            source_document_id: $document->source_document_id,
            converted_to_order_id: $convertedToOrderId,
            converted_at: $convertedAt,
            lines: $lines,
            created_at: $document->created_at?->toIso8601String() ?? '',
            updated_at: $document->updated_at?->toIso8601String() ?? '',
        );
    }
}
