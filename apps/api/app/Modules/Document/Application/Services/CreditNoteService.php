<?php

declare(strict_types=1);

namespace App\Modules\Document\Application\Services;

use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\CreditNoteReason;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    /**
     * Create a credit note from a source invoice.
     *
     * @throws \InvalidArgumentException
     */
    public function createCreditNote(
        string $sourceInvoiceId,
        string $amount,
        CreditNoteReason $reason,
        ?string $notes = null
    ): Document {
        $invoice = Document::findOrFail($sourceInvoiceId);

        // Validation: Only posted invoices can have credit notes
        if (!$invoice->isPosted()) {
            throw new \InvalidArgumentException('Credit notes can only be created for posted invoices');
        }

        // Validation: Credit note amount cannot exceed invoice total
        /** @phpstan-ignore-next-line argument.type */
        if (bccomp($amount, $invoice->total, 4) > 0) {
            throw new \InvalidArgumentException('Credit note amount cannot exceed invoice total');
        }

        // Validation: Total credit notes cannot exceed invoice total
        /** @var numeric-string $totalCreditNotes */
        $totalCreditNotes = $invoice->creditNotes()->sum('total');
        /** @phpstan-ignore-next-line argument.type */
        $remainingAmount = bcsub($invoice->total, (string) $totalCreditNotes, 4);

        /** @phpstan-ignore-next-line argument.type */
        if (bccomp($amount, $remainingAmount, 4) > 0) {
            throw new \InvalidArgumentException('Total credit notes would exceed invoice total');
        }

        return DB::transaction(function () use ($invoice, $amount, $reason, $notes): Document {
            $creditNoteNumber = $this->generateCreditNoteNumber($invoice->company_id);

            // Calculate proportional tax and subtotal
            /** @phpstan-ignore-next-line argument.type */
            $taxRate = bccomp($invoice->subtotal, '0', 4) > 0
                /** @phpstan-ignore-next-line argument.type */
                ? bcdiv($invoice->tax_amount ?? '0', $invoice->subtotal, 6)
                : '0';

            $subtotal = bcdiv($amount, bcadd('1', $taxRate, 6), 4);
            /** @phpstan-ignore-next-line argument.type */
            $taxAmount = bcsub($amount, $subtotal, 4);

            return Document::create([
                'tenant_id' => $invoice->tenant_id,
                'company_id' => $invoice->company_id,
                'partner_id' => $invoice->partner_id,
                'vehicle_id' => $invoice->vehicle_id,
                'location_id' => $invoice->location_id,
                'type' => DocumentType::CreditNote,
                'status' => DocumentStatus::Draft,
                'document_number' => $creditNoteNumber,
                'document_date' => now(),
                'currency' => $invoice->currency,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $amount,
                'source_document_id' => $invoice->id,
                'credit_note_reason' => $reason,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Generate sequential credit note number.
     */
    private function generateCreditNoteNumber(string $companyId): string
    {
        $lastCreditNote = Document::where('company_id', $companyId)
            ->where('type', DocumentType::CreditNote)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastCreditNote && preg_match('/CN-(\d+)/', $lastCreditNote->document_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('CN-%05d', $nextNumber);
    }
}
