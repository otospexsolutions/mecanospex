<?php

declare(strict_types=1);

namespace App\Modules\Document\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Services\DocumentNumberingService;
use App\Modules\Document\Domain\Services\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refundService,
        private readonly DocumentNumberingService $numberingService
    ) {}

    /**
     * Cancel an invoice
     */
    public function cancelInvoice(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $invoice = Document::findOrFail($id);

        try {
            $cancelled = $this->refundService->cancelInvoice(
                $invoice,
                (string) $request->input('reason')
            );

            return response()->json([
                'data' => $cancelled->load(['lines', 'partner']),
                'message' => 'Invoice cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a credit note
     */
    public function cancelCreditNote(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $creditNote = Document::findOrFail($id);

        try {
            $cancelled = $this->refundService->cancelCreditNote(
                $creditNote,
                (string) $request->input('reason')
            );

            return response()->json([
                'data' => $cancelled->load(['lines', 'partner']),
                'message' => 'Credit note cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a full credit note from an invoice
     */
    public function createFullCreditNote(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $invoice = Document::findOrFail($id);

        try {
            $creditNote = $this->refundService->createFullCreditNote(
                $invoice,
                (string) $request->input('reason'),
                $this->numberingService
            );

            return response()->json([
                'data' => $creditNote->load(['lines', 'partner']),
                'message' => 'Full credit note created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Create a partial credit note from an invoice
     */
    public function createPartialCreditNote(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'line_items' => 'required|array|min:1',
            'line_items.*.product_id' => 'nullable|exists:products,id',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|numeric|min:0.01',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'line_items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
            'line_items.*.discount_amount' => 'nullable|numeric|min:0',
            'line_items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'line_items.*.tax_amount' => 'nullable|numeric|min:0',
            'line_items.*.subtotal' => 'required|numeric|min:0',
            'line_items.*.total' => 'required|numeric|min:0',
        ]);

        $invoice = Document::findOrFail($id);

        try {
            $creditNote = $this->refundService->createPartialCreditNote(
                $invoice,
                $request->input('line_items'),
                (string) $request->input('reason'),
                $this->numberingService
            );

            return response()->json([
                'data' => $creditNote->load(['lines', 'partner']),
                'message' => 'Partial credit note created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if invoice can be cancelled
     */
    public function checkCancellable(string $id): JsonResponse
    {
        $invoice = Document::findOrFail($id);

        try {
            $canCancel = $this->refundService->canCancelInvoice($invoice);

            return response()->json([
                'data' => [
                    'can_cancel' => $canCancel,
                    'status' => $invoice->status->value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if invoice can be credited
     */
    public function checkCreditable(string $id): JsonResponse
    {
        $invoice = Document::findOrFail($id);

        try {
            $canCredit = $this->refundService->canCreditInvoice($invoice);

            return response()->json([
                'data' => [
                    'can_credit' => $canCredit,
                    'status' => $invoice->status->value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get credit note summary for an invoice
     */
    public function getCreditNoteSummary(string $id): JsonResponse
    {
        $invoice = Document::findOrFail($id);

        try {
            $summary = $this->refundService->getCreditNoteSummary($invoice);

            return response()->json([
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
