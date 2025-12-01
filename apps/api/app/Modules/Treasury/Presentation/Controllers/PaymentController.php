<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Presentation\Controllers;

use App\Modules\Company\Services\CompanyContext;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Treasury\Domain\Enums\PaymentStatus;
use App\Modules\Treasury\Domain\Payment;
use App\Modules\Treasury\Domain\PaymentAllocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        $query = Payment::query()
            ->where('tenant_id', $tenantId)
            ->with(['partner', 'paymentMethod', 'allocations.document']);

        // Filter by partner
        if ($request->has('partner_id')) {
            $query->where('partner_id', $request->input('partner_id'));
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $payments = $query->orderByDesc('payment_date')->get();

        return response()->json([
            'data' => $payments->map(fn (Payment $payment) => $this->formatPayment($payment)),
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        $payment = Payment::query()
            ->where('tenant_id', $tenantId)
            ->with(['partner', 'paymentMethod', 'allocations.document'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatPayment($payment),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = $this->companyContext->requireCompanyId();
        $company = $this->companyContext->requireCompany();
        $tenantId = $company->tenant_id;

        $validated = $request->validate([
            'partner_id' => ['required', 'uuid', 'exists:partners,id'],
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'instrument_id' => ['nullable', 'uuid', 'exists:payment_instruments,id'],
            'repository_id' => ['nullable', 'uuid', 'exists:payment_repositories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.document_id' => ['required_with:allocations', 'uuid', 'exists:documents,id'],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01'],
        ]);

        /** @var numeric-string $paymentAmount */
        $paymentAmount = (string) $validated['amount'];

        // Validate allocations don't exceed payment amount
        $allocations = $validated['allocations'] ?? [];
        /** @var numeric-string $totalAllocated */
        $totalAllocated = '0.00';
        foreach ($allocations as $allocation) {
            /** @var numeric-string $allocationAmt */
            $allocationAmt = (string) $allocation['amount'];
            $totalAllocated = bcadd($totalAllocated, $allocationAmt, 2);
        }

        if (bccomp($totalAllocated, $paymentAmount, 2) > 0) {
            return response()->json([
                'error' => [
                    'code' => 'ALLOCATION_EXCEEDS_PAYMENT',
                    'message' => 'Total allocation amount exceeds payment amount',
                ],
            ], 422);
        }

        // Validate each allocation doesn't exceed document balance
        foreach ($allocations as $allocation) {
            /** @var Document $document */
            $document = Document::findOrFail($allocation['document_id']);

            /** @var numeric-string $allocationAmount */
            $allocationAmount = (string) $allocation['amount'];

            /** @var numeric-string $balanceDue */
            $balanceDue = $document->balance_due ?? '0.00';

            if (bccomp($allocationAmount, $balanceDue, 2) > 0) {
                return response()->json([
                    'error' => [
                        'code' => 'OVERPAYMENT',
                        'message' => "Allocation amount exceeds document balance for {$document->document_number}",
                        'details' => [
                            'document_id' => $document->id,
                            'document_number' => $document->document_number,
                            'balance_due' => $document->balance_due,
                            'allocation_amount' => $allocationAmount,
                        ],
                    ],
                ], 422);
            }
        }

        // Create payment and allocations in a transaction
        $payment = DB::transaction(function () use ($validated, $user, $paymentAmount, $allocations, $tenantId, $companyId) {
            $payment = Payment::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'partner_id' => $validated['partner_id'],
                'payment_method_id' => $validated['payment_method_id'],
                'instrument_id' => $validated['instrument_id'] ?? null,
                'repository_id' => $validated['repository_id'] ?? null,
                'amount' => $paymentAmount,
                'currency' => $validated['currency'] ?? 'TND',
                'payment_date' => $validated['payment_date'],
                'status' => PaymentStatus::Completed,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Create allocations and update document balances
            foreach ($allocations as $allocationData) {
                /** @var Document $document */
                $document = Document::lockForUpdate()->findOrFail($allocationData['document_id']);

                /** @var numeric-string $allocationAmount */
                $allocationAmount = (string) $allocationData['amount'];

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $document->id,
                    'amount' => $allocationAmount,
                ]);

                // Update document balance
                /** @var numeric-string $currentBalance */
                $currentBalance = $document->balance_due ?? '0.00';
                $newBalance = bcsub($currentBalance, $allocationAmount, 2);
                $document->balance_due = $newBalance;

                // Mark as paid if fully paid
                if (bccomp($newBalance, '0.00', 2) === 0) {
                    $document->status = DocumentStatus::Paid;
                }

                $document->save();
            }

            return $payment;
        });

        $payment->load(['partner', 'paymentMethod', 'allocations.document']);

        return response()->json([
            'data' => $this->formatPayment($payment),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'partner_id' => $payment->partner_id,
            'partner' => $payment->partner ? [
                'id' => $payment->partner->id,
                'name' => $payment->partner->name,
            ] : null,
            'payment_method_id' => $payment->payment_method_id,
            'payment_method' => $payment->paymentMethod ? [
                'id' => $payment->paymentMethod->id,
                'code' => $payment->paymentMethod->code,
                'name' => $payment->paymentMethod->name,
            ] : null,
            'instrument_id' => $payment->instrument_id,
            'repository_id' => $payment->repository_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_date' => $payment->payment_date->toDateString(),
            'status' => $payment->status->value,
            'reference' => $payment->reference,
            'notes' => $payment->notes,
            'allocations' => $payment->allocations->map(fn (PaymentAllocation $allocation) => [
                'id' => $allocation->id,
                'document_id' => $allocation->document_id,
                'document_number' => $allocation->document->document_number,
                'amount' => $allocation->amount,
            ])->toArray(),
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }
}
