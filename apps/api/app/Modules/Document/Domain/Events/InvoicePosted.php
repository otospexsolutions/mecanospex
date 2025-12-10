<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;

/**
 * Event raised when an invoice or credit note is posted.
 *
 * This is a fiscal event that is part of the hash chain for NF525/ZATCA compliance.
 * Once dispatched, this event is immutable and should never be modified.
 *
 * @see https://www.legifrance.gouv.fr/loda/id/JORFTEXT000033735427 NF525 Requirements
 */
final class InvoicePosted extends DomainEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $tenantId,
        public readonly string $companyId,
        public readonly string $documentNumber,
        public readonly string $documentType,
        public readonly string $partnerId,
        public readonly string $total,
        public readonly string $currency,
        public readonly string $fiscalHash,
        public readonly int $chainSequence,
        public readonly string $postedAt,
    ) {
        parent::__construct($invoiceId);
    }

    /**
     * Get the data to be used for hash chain calculation.
     *
     * @return array<string, string|int>
     */
    public function getHashableData(): array
    {
        return [
            'document_number' => $this->documentNumber,
            'document_type' => $this->documentType,
            'posted_at' => $this->postedAt,
            'total' => $this->total,
            'currency' => $this->currency,
            'fiscal_hash' => $this->fiscalHash,
            'chain_sequence' => $this->chainSequence,
        ];
    }

    /**
     * Get the event name for logging purposes.
     */
    public function getEventName(): string
    {
        return 'invoice.posted';
    }
}
