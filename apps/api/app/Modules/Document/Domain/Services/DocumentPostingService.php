<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Services;

use App\Modules\Compliance\Services\FiscalHashService;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Document\Domain\Events\InvoiceCancelled;
use App\Modules\Document\Domain\Events\InvoicePosted;
use Illuminate\Support\Facades\DB;

/**
 * Service responsible for posting and cancelling documents with NF525 compliance.
 *
 * This service implements the "Event-first, state-second" pattern required for
 * fiscal compliance. All fiscal documents (invoices, credit notes) are added
 * to a SHA-256 hash chain that ensures tamper-proof audit trails.
 *
 * Hash Chain Format: SHA256(previous_hash | document_number | date | total | currency)
 */
final class DocumentPostingService
{
    /**
     * Document types that require fiscal hash chain compliance.
     *
     * @var list<DocumentType>
     */
    private const FISCAL_DOCUMENT_TYPES = [
        DocumentType::Invoice,
        DocumentType::CreditNote,
    ];

    public function __construct(
        private readonly FiscalHashService $hashService,
    ) {}

    /**
     * Post a document, adding it to the fiscal hash chain if required.
     *
     * @throws \DomainException If document cannot be posted
     */
    public function post(Document $document): Document
    {
        if (! $document->isConfirmed()) {
            throw new \DomainException(
                'Only confirmed documents can be posted. Current status: '.$document->status->value
            );
        }

        $requiresFiscalChain = $this->requiresFiscalChain($document->type);

        return DB::transaction(function () use ($document, $requiresFiscalChain): Document {
            if ($requiresFiscalChain) {
                $this->postWithFiscalChain($document);
            } else {
                $document->update(['status' => DocumentStatus::Posted]);
            }

            /** @var Document */
            return $document->fresh(['lines']);
        });
    }

    /**
     * Cancel a posted document, recording the cancellation in the fiscal chain.
     *
     * @throws \DomainException If document cannot be cancelled
     */
    public function cancel(Document $document): Document
    {
        if (! $document->isPosted()) {
            throw new \DomainException(
                'Only posted documents can be cancelled. Current status: '.$document->status->value
            );
        }

        $requiresFiscalChain = $this->requiresFiscalChain($document->type);

        return DB::transaction(function () use ($document, $requiresFiscalChain): Document {
            $document->update(['status' => DocumentStatus::Cancelled]);

            if ($requiresFiscalChain) {
                $this->dispatchCancellationEvent($document);
            }

            /** @var Document */
            return $document->fresh(['lines']);
        });
    }

    /**
     * Post a document with full fiscal hash chain compliance.
     */
    private function postWithFiscalChain(Document $document): void
    {
        // Acquire lock and get previous document in chain
        $previousDoc = Document::where('company_id', $document->company_id)
            ->where('type', $document->type)
            ->where('status', DocumentStatus::Posted)
            ->whereNotNull('fiscal_hash')
            ->orderByDesc('chain_sequence')
            ->lockForUpdate()
            ->first();

        $previousHash = $previousDoc?->fiscal_hash;
        $chainSequence = ($previousDoc?->chain_sequence ?? 0) + 1;
        $postedAt = now();

        // Calculate fiscal hash using the compliance service
        $input = $this->hashService->serializeForHashing([
            'document_number' => $document->document_number,
            'posted_at' => $postedAt->toDateString(),
            'total' => $document->total ?? '0.00',
            'currency' => $document->currency,
        ]);

        $fiscalHash = $this->hashService->calculateHash($input, $previousHash);

        // Update document with fiscal chain data
        $document->update([
            'status' => DocumentStatus::Posted,
            'fiscal_hash' => $fiscalHash,
            'previous_hash' => $previousHash,
            'chain_sequence' => $chainSequence,
        ]);

        // Dispatch the fiscal event for audit log
        $this->dispatchPostedEvent($document, $postedAt->toIso8601String());
    }

    /**
     * Dispatch the InvoicePosted event for fiscal audit trail.
     */
    private function dispatchPostedEvent(Document $document, string $postedAt): void
    {
        event(new InvoicePosted(
            invoiceId: $document->id,
            tenantId: $document->tenant_id,
            companyId: $document->company_id,
            documentNumber: $document->document_number,
            documentType: $document->type->value,
            partnerId: $document->partner_id,
            total: $document->total ?? '0.00',
            currency: $document->currency,
            fiscalHash: $document->fiscal_hash ?? '',
            chainSequence: $document->chain_sequence ?? 0,
            postedAt: $postedAt,
        ));
    }

    /**
     * Dispatch the InvoiceCancelled event for fiscal audit trail.
     */
    private function dispatchCancellationEvent(Document $document): void
    {
        event(new InvoiceCancelled(
            invoiceId: $document->id,
            tenantId: $document->tenant_id,
            companyId: $document->company_id,
            documentNumber: $document->document_number,
            documentType: $document->type->value,
            originalFiscalHash: $document->fiscal_hash ?? '',
            cancelledAt: now()->toIso8601String(),
        ));
    }

    /**
     * Check if a document type requires fiscal hash chain.
     */
    private function requiresFiscalChain(DocumentType $type): bool
    {
        return in_array($type, self::FISCAL_DOCUMENT_TYPES, true);
    }

    /**
     * Get the list of document types that require fiscal compliance.
     *
     * @return list<DocumentType>
     */
    public static function getFiscalDocumentTypes(): array
    {
        return self::FISCAL_DOCUMENT_TYPES;
    }
}
