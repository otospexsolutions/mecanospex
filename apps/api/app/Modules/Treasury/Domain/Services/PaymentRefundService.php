<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Services;

use App\Modules\Treasury\Domain\Enums\PaymentStatus;
use App\Modules\Treasury\Domain\Payment;
use App\Modules\Treasury\Domain\PaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentRefundService
{
    /**
     * Refund a completed payment
     */
    public function refundPayment(
        Payment $payment,
        string $reason,
        ?string $userId = null
    ): Payment {
        if ($payment->status !== PaymentStatus::Completed) {
            throw new \RuntimeException('Only completed payments can be refunded');
        }

        // Check if already refunded
        if ($payment->status === PaymentStatus::Reversed) {
            throw new \RuntimeException('Payment has already been refunded');
        }

        return DB::transaction(function () use ($payment, $reason, $userId): Payment {
            // Create refund payment (negative amount)
            $refund = Payment::create([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $payment->tenant_id,
                'company_id' => $payment->company_id,
                'partner_id' => $payment->partner_id,
                'payment_method_id' => $payment->payment_method_id,
                'instrument_id' => $payment->instrument_id,
                'repository_id' => $payment->repository_id,
                'amount' => bcmul($payment->amount, '-1', 2), // Negative amount
                'currency' => $payment->currency,
                'payment_date' => now(),
                'status' => PaymentStatus::Completed,
                'reference' => "Refund for payment {$payment->reference}",
                'notes' => "Refund: {$reason}",
                'created_by' => $userId,
            ]);

            // Reverse original payment allocations
            $originalAllocations = $payment->allocations;

            foreach ($originalAllocations as $allocation) {
                PaymentAllocation::create([
                    'id' => Str::uuid()->toString(),
                    'payment_id' => $refund->id,
                    'document_id' => $allocation->document_id,
                    'amount' => bcmul($allocation->amount, '-1', 2), // Negative amount
                    'notes' => "Refund allocation for {$allocation->document_id}",
                ]);
            }

            // Mark original payment as reversed
            $payment->update([
                'status' => PaymentStatus::Reversed,
                'notes' => ($payment->notes ?? '')."\n\nRefunded: {$reason}",
            ]);

            return $refund;
        });
    }

    /**
     * Partially refund a payment
     */
    public function partialRefund(
        Payment $payment,
        string $amount,
        string $reason,
        ?string $userId = null
    ): Payment {
        if ($payment->status !== PaymentStatus::Completed) {
            throw new \RuntimeException('Only completed payments can be refunded');
        }

        // Validate refund amount
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('Refund amount must be greater than zero');
        }

        if (bccomp($amount, $payment->amount, 2) > 0) {
            throw new \InvalidArgumentException('Refund amount cannot exceed original payment amount');
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $userId): Payment {
            // Create partial refund payment (negative amount)
            $refund = Payment::create([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $payment->tenant_id,
                'company_id' => $payment->company_id,
                'partner_id' => $payment->partner_id,
                'payment_method_id' => $payment->payment_method_id,
                'instrument_id' => $payment->instrument_id,
                'repository_id' => $payment->repository_id,
                'amount' => bcmul($amount, '-1', 2), // Negative amount
                'currency' => $payment->currency,
                'payment_date' => now(),
                'status' => PaymentStatus::Completed,
                'reference' => "Partial refund for payment {$payment->reference}",
                'notes' => "Partial refund ({$amount}): {$reason}",
                'created_by' => $userId,
            ]);

            // Update original payment notes
            $payment->update([
                'notes' => ($payment->notes ?? '')."\n\nPartial refund of {$amount}: {$reason}",
            ]);

            return $refund;
        });
    }

    /**
     * Check if payment can be refunded
     */
    public function canRefund(Payment $payment): bool
    {
        return $payment->status === PaymentStatus::Completed;
    }

    /**
     * Get refund history for a payment
     */
    public function getRefundHistory(Payment $payment): array
    {
        // Find all refund payments (negative amounts) for this payment
        $refunds = Payment::where('tenant_id', $payment->tenant_id)
            ->where('partner_id', $payment->partner_id)
            ->where('amount', '<', '0')
            ->where('reference', 'like', '%'.$payment->reference.'%')
            ->get();

        $totalRefunded = '0.00';
        foreach ($refunds as $refund) {
            $totalRefunded = bcadd($totalRefunded, abs((float) $refund->amount), 2);
        }

        return [
            'original_amount' => $payment->amount,
            'total_refunded' => $totalRefunded,
            'remaining_amount' => bcsub($payment->amount, $totalRefunded, 2),
            'is_fully_refunded' => bccomp($totalRefunded, $payment->amount, 2) >= 0,
            'refund_count' => $refunds->count(),
            'refunds' => $refunds,
        ];
    }

    /**
     * Reverse a payment (for errors/corrections)
     */
    public function reversePayment(
        Payment $payment,
        string $reason,
        ?string $userId = null
    ): void {
        if ($payment->status === PaymentStatus::Reversed) {
            throw new \RuntimeException('Payment has already been reversed');
        }

        if (! in_array($payment->status, [PaymentStatus::Completed, PaymentStatus::Failed], true)) {
            throw new \RuntimeException('Only completed or failed payments can be reversed');
        }

        DB::transaction(function () use ($payment, $reason): void {
            // Delete allocations
            PaymentAllocation::where('payment_id', $payment->id)->delete();

            // Update payment status
            $payment->update([
                'status' => PaymentStatus::Reversed,
                'notes' => ($payment->notes ?? '')."\n\nReversed: {$reason}",
            ]);
        });
    }
}
