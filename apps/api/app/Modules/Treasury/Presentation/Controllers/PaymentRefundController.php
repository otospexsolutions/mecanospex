<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treasury\Domain\Payment;
use App\Modules\Treasury\Domain\Services\PaymentRefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRefundController extends Controller
{
    public function __construct(
        private readonly PaymentRefundService $refundService
    ) {}

    /**
     * Refund a complete payment
     */
    public function refundPayment(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        try {
            $refund = $this->refundService->refundPayment(
                $payment,
                (string) $request->input('reason'),
                $request->user()?->id
            );

            return response()->json([
                'data' => $refund->load(['partner', 'paymentMethod', 'allocations']),
                'message' => 'Payment refunded successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Partially refund a payment
     */
    public function partialRefund(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        try {
            $refund = $this->refundService->partialRefund(
                $payment,
                (string) $request->input('amount'),
                (string) $request->input('reason'),
                $request->user()?->id
            );

            return response()->json([
                'data' => $refund->load(['partner', 'paymentMethod']),
                'message' => 'Partial refund created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reverse a payment (for errors/corrections)
     */
    public function reversePayment(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $payment = Payment::findOrFail($id);

        try {
            $this->refundService->reversePayment(
                $payment,
                (string) $request->input('reason'),
                $request->user()?->id
            );

            return response()->json([
                'data' => $payment->fresh(),
                'message' => 'Payment reversed successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check if payment can be refunded
     */
    public function checkRefundable(string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        try {
            $canRefund = $this->refundService->canRefund($payment);

            return response()->json([
                'data' => [
                    'can_refund' => $canRefund,
                    'status' => $payment->status->value,
                    'amount' => $payment->amount,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get refund history for a payment
     */
    public function getRefundHistory(string $id): JsonResponse
    {
        $payment = Payment::findOrFail($id);

        try {
            $history = $this->refundService->getRefundHistory($payment);

            return response()->json([
                'data' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
