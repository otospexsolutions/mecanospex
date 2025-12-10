<?php

declare(strict_types=1);

namespace App\Modules\Document\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Services\DocumentConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentConversionController extends Controller
{
    public function __construct(
        private readonly DocumentConversionService $conversionService
    ) {}

    /**
     * Convert a quote to a sales order
     */
    public function convertQuoteToOrder(string $id): JsonResponse
    {
        $quote = Document::findOrFail($id);

        try {
            $order = $this->conversionService->convertQuoteToOrder($quote);

            return response()->json([
                'data' => $order->load(['lines', 'partner', 'vehicle']),
                'message' => 'Quote converted to sales order successfully',
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => [
                    'code' => 'QUOTE_NOT_CONFIRMED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Convert a sales order to an invoice
     */
    public function convertOrderToInvoice(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'partial' => 'sometimes|boolean',
            'line_ids' => 'sometimes|array',
            'line_ids.*' => 'exists:document_lines,id',
        ]);

        $order = Document::findOrFail($id);

        try {
            $invoice = $this->conversionService->convertOrderToInvoice(
                $order,
                (bool) $request->input('partial', false),
                $request->input('line_ids')
            );

            return response()->json([
                'data' => $invoice->load(['lines', 'partner', 'vehicle']),
                'message' => 'Sales order converted to invoice successfully',
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => [
                    'code' => 'ORDER_NOT_CONFIRMED',
                    'message' => $e->getMessage(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Convert a sales order to a delivery note
     */
    public function convertOrderToDelivery(string $id): JsonResponse
    {
        $order = Document::findOrFail($id);

        try {
            $delivery = $this->conversionService->convertOrderToDelivery($order);

            return response()->json([
                'data' => $delivery->load(['lines', 'partner', 'vehicle']),
                'message' => 'Sales order converted to delivery note successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if a quote has expired
     */
    public function checkQuoteExpiry(string $id): JsonResponse
    {
        $quote = Document::findOrFail($id);

        try {
            $isExpired = $this->conversionService->isQuoteExpired($quote);

            return response()->json([
                'data' => [
                    'is_expired' => $isExpired,
                    'valid_until' => $quote->valid_until?->toDateTimeString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if an order has been fully invoiced
     */
    public function checkOrderInvoiceStatus(string $id): JsonResponse
    {
        $order = Document::findOrFail($id);

        try {
            $isFullyInvoiced = $this->conversionService->isOrderFullyInvoiced($order);

            return response()->json([
                'data' => [
                    'fully_invoiced' => $isFullyInvoiced,
                    'invoice_ids' => $order->payload['invoice_ids'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
