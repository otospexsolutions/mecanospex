<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;

/**
 * Event raised when an invoice is posted.
 *
 * This is a fiscal event that will be part of the hash chain
 * for compliance purposes.
 */
final class InvoicePosted extends DomainEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $tenantId,
        public readonly string $documentNumber,
        public readonly string $partnerId,
        public readonly string $total,
        public readonly string $currency,
        public readonly string $postedAt,
    ) {
        parent::__construct($invoiceId);
    }

    /**
     * Get the data to be used for hash chain calculation.
     *
     * @return array<string, string>
     */
    public function getHashableData(): array
    {
        return [
            'document_number' => $this->documentNumber,
            'posted_at' => $this->postedAt,
            'total' => $this->total,
            'currency' => $this->currency,
        ];
    }
}
