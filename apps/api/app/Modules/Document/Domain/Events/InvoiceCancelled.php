<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Events;

use App\Shared\Domain\Events\DomainEvent;

/**
 * Event raised when a posted invoice or credit note is cancelled.
 *
 * This is a fiscal event for NF525/ZATCA compliance. Cancellations are recorded
 * in the audit trail but do not break the hash chain - the original document
 * remains part of the chain with its status changed.
 *
 * Note: Once dispatched, this event is immutable and should never be modified.
 */
final class InvoiceCancelled extends DomainEvent
{
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $tenantId,
        public readonly string $companyId,
        public readonly string $documentNumber,
        public readonly string $documentType,
        public readonly string $originalFiscalHash,
        public readonly string $cancelledAt,
    ) {
        parent::__construct($invoiceId);
    }

    /**
     * Get the data for audit logging.
     *
     * @return array<string, string>
     */
    public function getAuditData(): array
    {
        return [
            'document_number' => $this->documentNumber,
            'document_type' => $this->documentType,
            'original_fiscal_hash' => $this->originalFiscalHash,
            'cancelled_at' => $this->cancelledAt,
        ];
    }

    /**
     * Get the event name for logging purposes.
     */
    public function getEventName(): string
    {
        return 'invoice.cancelled';
    }
}
