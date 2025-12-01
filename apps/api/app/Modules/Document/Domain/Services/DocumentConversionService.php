<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Services;

use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\DocumentLine;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentConversionService
{
    public function __construct(
        private readonly DocumentNumberingService $numberingService
    ) {}

    /**
     * Convert a quote to a sales order
     */
    public function convertQuoteToOrder(Document $quote): Document
    {
        if ($quote->type !== DocumentType::Quote) {
            throw new \InvalidArgumentException('Source document must be a quote');
        }

        if ($quote->status === DocumentStatus::Cancelled) {
            throw new \RuntimeException('Cannot convert cancelled quote');
        }

        // Check if quote is expired
        if ($quote->valid_until && $quote->valid_until->isPast()) {
            throw new \RuntimeException('Cannot convert expired quote');
        }

        return DB::transaction(function () use ($quote): Document {
            // Create sales order
            $order = Document::create([
                'tenant_id' => $quote->tenant_id,
                'company_id' => $quote->company_id,
                'location_id' => $quote->location_id,
                'partner_id' => $quote->partner_id,
                'vehicle_id' => $quote->vehicle_id,
                'type' => DocumentType::SalesOrder,
                'status' => DocumentStatus::Draft,
                'document_number' => $this->numberingService->generateNumber(
                    $quote->company_id,
                    DocumentType::SalesOrder
                ),
                'document_date' => now(),
                'currency' => $quote->currency,
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'tax_amount' => $quote->tax_amount,
                'total' => $quote->total,
                'balance_due' => $quote->total,
                'notes' => $quote->notes,
                'internal_notes' => $quote->internal_notes,
                'reference' => $quote->document_number,
                'source_document_id' => $quote->id,
            ]);

            // Copy lines
            $this->copyLines($quote, $order);

            // Mark quote as converted
            $quote->update([
                'payload' => array_merge($quote->payload ?? [], [
                    'converted_to_order_id' => $order->id,
                    'converted_at' => now()->toDateTimeString(),
                ]),
            ]);

            return $order;
        });
    }

    /**
     * Convert a sales order to an invoice
     */
    public function convertOrderToInvoice(Document $order, bool $partial = false, ?array $lineIds = null): Document
    {
        if ($order->type !== DocumentType::SalesOrder) {
            throw new \InvalidArgumentException('Source document must be a sales order');
        }

        if ($order->status === DocumentStatus::Cancelled) {
            throw new \RuntimeException('Cannot convert cancelled sales order');
        }

        return DB::transaction(function () use ($order, $partial, $lineIds): Document {
            $invoice = Document::create([
                'tenant_id' => $order->tenant_id,
                'company_id' => $order->company_id,
                'location_id' => $order->location_id,
                'partner_id' => $order->partner_id,
                'vehicle_id' => $order->vehicle_id,
                'type' => DocumentType::Invoice,
                'status' => DocumentStatus::Draft,
                'document_number' => $this->numberingService->generateNumber(
                    $order->company_id,
                    DocumentType::Invoice
                ),
                'document_date' => now(),
                'due_date' => now()->addDays(30),
                'currency' => $order->currency,
                'notes' => $order->notes,
                'internal_notes' => $order->internal_notes,
                'reference' => $order->document_number,
                'source_document_id' => $order->id,
            ]);

            // Copy lines (all or partial)
            if ($partial && $lineIds !== null) {
                $this->copyPartialLines($order, $invoice, $lineIds);
            } else {
                $this->copyLines($order, $invoice);
            }

            // Recalculate totals
            $this->recalculateTotals($invoice);

            // Update order payload
            $orderPayload = $order->payload ?? [];
            $orderPayload['invoice_ids'] = array_merge(
                $orderPayload['invoice_ids'] ?? [],
                [$invoice->id]
            );

            if (! $partial) {
                $orderPayload['fully_invoiced'] = true;
                $orderPayload['fully_invoiced_at'] = now()->toDateTimeString();
            }

            $order->update(['payload' => $orderPayload]);

            return $invoice;
        });
    }

    /**
     * Convert a sales order to a delivery note
     */
    public function convertOrderToDelivery(Document $order): Document
    {
        if ($order->type !== DocumentType::SalesOrder) {
            throw new \InvalidArgumentException('Source document must be a sales order');
        }

        if ($order->status === DocumentStatus::Cancelled) {
            throw new \RuntimeException('Cannot convert cancelled sales order');
        }

        return DB::transaction(function () use ($order): Document {
            $delivery = Document::create([
                'tenant_id' => $order->tenant_id,
                'company_id' => $order->company_id,
                'location_id' => $order->location_id,
                'partner_id' => $order->partner_id,
                'vehicle_id' => $order->vehicle_id,
                'type' => DocumentType::DeliveryNote,
                'status' => DocumentStatus::Draft,
                'document_number' => $this->numberingService->generateNumber(
                    $order->company_id,
                    DocumentType::DeliveryNote
                ),
                'document_date' => now(),
                'currency' => $order->currency,
                'subtotal' => $order->subtotal,
                'discount_amount' => $order->discount_amount,
                'tax_amount' => $order->tax_amount,
                'total' => $order->total,
                'notes' => $order->notes,
                'internal_notes' => $order->internal_notes,
                'reference' => $order->document_number,
                'source_document_id' => $order->id,
            ]);

            // Copy lines
            $this->copyLines($order, $delivery);

            // Mark order as delivered
            $order->update([
                'payload' => array_merge($order->payload ?? [], [
                    'delivery_note_id' => $delivery->id,
                    'delivered_at' => now()->toDateTimeString(),
                ]),
            ]);

            return $delivery;
        });
    }

    /**
     * Copy all lines from source to destination document
     */
    private function copyLines(Document $source, Document $destination): void
    {
        foreach ($source->lines as $line) {
            DocumentLine::create([
                'id' => Str::uuid()->toString(),
                'document_id' => $destination->id,
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'discount_amount' => $line->discount_amount,
                'tax_rate' => $line->tax_rate,
                'tax_amount' => $line->tax_amount,
                'subtotal' => $line->subtotal,
                'total' => $line->total,
                'sort_order' => $line->sort_order,
            ]);
        }
    }

    /**
     * Copy selected lines from source to destination document
     */
    private function copyPartialLines(Document $source, Document $destination, array $lineIds): void
    {
        $lines = $source->lines()->whereIn('id', $lineIds)->get();

        foreach ($lines as $line) {
            DocumentLine::create([
                'id' => Str::uuid()->toString(),
                'document_id' => $destination->id,
                'product_id' => $line->product_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_percent' => $line->discount_percent,
                'discount_amount' => $line->discount_amount,
                'tax_rate' => $line->tax_rate,
                'tax_amount' => $line->tax_amount,
                'subtotal' => $line->subtotal,
                'total' => $line->total,
                'sort_order' => $line->sort_order,
            ]);
        }
    }

    /**
     * Recalculate document totals based on lines
     */
    private function recalculateTotals(Document $document): void
    {
        $subtotal = '0.00';
        $taxAmount = '0.00';
        $total = '0.00';

        foreach ($document->lines as $line) {
            $subtotal = bcadd($subtotal, $line->subtotal, 2);
            $taxAmount = bcadd($taxAmount, $line->tax_amount, 2);
            $total = bcadd($total, $line->total, 2);
        }

        $document->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'balance_due' => $total,
        ]);
    }

    /**
     * Check if quote has expired
     */
    public function isQuoteExpired(Document $quote): bool
    {
        if ($quote->type !== DocumentType::Quote) {
            throw new \InvalidArgumentException('Document must be a quote');
        }

        return $quote->valid_until !== null && $quote->valid_until->isPast();
    }

    /**
     * Check if order has been fully invoiced
     */
    public function isOrderFullyInvoiced(Document $order): bool
    {
        if ($order->type !== DocumentType::SalesOrder) {
            throw new \InvalidArgumentException('Document must be a sales order');
        }

        $payload = $order->payload ?? [];

        return $payload['fully_invoiced'] ?? false;
    }
}
