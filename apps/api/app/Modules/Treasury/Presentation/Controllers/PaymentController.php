<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Presentation\Controllers;

use App\Modules\Accounting\Domain\Services\GeneralLedgerService;
use App\Modules\Company\Services\CompanyContext;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Treasury\Domain\Enums\PaymentStatus;
use App\Modules\Treasury\Domain\Enums\PaymentType;
use App\Modules\Treasury\Domain\Payment;
use App\Modules\Treasury\Domain\PaymentAllocation;
use App\Modules\Treasury\Domain\PaymentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly GeneralLedgerService $glService,
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

        // Validate each allocation - cap it at document balance (no overpayment per invoice)
        // Excess will be handled as customer advance
        $adjustedAllocations = [];
        foreach ($allocations as $allocation) {
            /** @var Document $document */
            $document = Document::findOrFail($allocation['document_id']);

            /** @var numeric-string $requestedAmount */
            $requestedAmount = (string) $allocation['amount'];

            /** @var numeric-string $balanceDue */
            $balanceDue = $document->balance_due ?? $document->total;

            // Cap allocation at document balance (can't overpay a single invoice)
            /** @var numeric-string $allocationAmount */
            $allocationAmount = bccomp($requestedAmount, $balanceDue, 2) > 0
                ? $balanceDue
                : $requestedAmount;

            if (bccomp($allocationAmount, '0', 2) > 0) {
                $adjustedAllocations[] = [
                    'document_id' => $document->id,
                    'amount' => $allocationAmount,
                ];
            }
        }

        // Create payment and allocations in a transaction
        $payment = DB::transaction(function () use ($validated, $user, $paymentAmount, $adjustedAllocations, $tenantId, $companyId) {
            // Determine payment type: advance if no allocations, otherwise document payment
            $paymentType = empty($adjustedAllocations)
                ? PaymentType::Advance
                : PaymentType::DocumentPayment;

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
                'payment_type' => $paymentType,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Calculate total allocated for GL entry
            /** @var numeric-string $totalAllocatedForGL */
            $totalAllocatedForGL = '0.00';

            // Create allocations and update document balances
            foreach ($adjustedAllocations as $allocationData) {
                /** @var Document $document */
                $document = Document::lockForUpdate()->findOrFail($allocationData['document_id']);

                /** @var numeric-string $allocationAmount */
                $allocationAmount = (string) $allocationData['amount'];

                PaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'document_id' => $document->id,
                    'amount' => $allocationAmount,
                ]);

                $totalAllocatedForGL = bcadd($totalAllocatedForGL, $allocationAmount, 2);

                // Update document balance
                /** @var numeric-string $currentBalance */
                $currentBalance = $document->balance_due ?? $document->total;
                $newBalance = bcsub($currentBalance, $allocationAmount, 2);
                $document->balance_due = $newBalance;

                // Mark as paid if fully paid
                if (bccomp($newBalance, '0.00', 2) === 0) {
                    $document->status = DocumentStatus::Paid;
                }

                $document->save();
            }

            // Update repository balance and create GL journal entry
            $repositoryId = $validated['repository_id'] ?? null;
            /** @var PaymentRepository|null $repository */
            $repository = null;

            if ($repositoryId) {
                /** @var PaymentRepository|null $repository */
                $repository = PaymentRepository::lockForUpdate()->find($repositoryId);

                if ($repository) {
                    // Increment repository balance by payment amount
                    /** @var numeric-string $currentBalance */
                    $currentBalance = $repository->balance ?? '0.00';
                    $repository->balance = bcadd($currentBalance, $paymentAmount, 2);
                    $repository->save();
                }
            }

            if ($repositoryId && bccomp($totalAllocatedForGL, '0', 2) > 0) {
                // Ensure repository is loaded if not already
                $repository = $repository ?? PaymentRepository::find($repositoryId);

                if ($repository && $repository->account_id) {
                    $journalEntry = $this->glService->createPaymentReceivedJournalEntry(
                        companyId: $companyId,
                        partnerId: $validated['partner_id'],
                        paymentId: $payment->id,
                        amount: $totalAllocatedForGL,
                        paymentMethodAccountId: $repository->account_id,
                        date: new \DateTimeImmutable($validated['payment_date']),
                        description: "Customer payment - {$payment->reference}"
                    );

                    // Link journal entry to payment
                    $payment->journal_entry_id = $journalEntry->id;
                    $payment->save();
                }
            }

            // Handle excess amount as customer advance
            /** @var numeric-string $excessAmount */
            $excessAmount = bcsub($paymentAmount, $totalAllocatedForGL, 2);

            if (bccomp($excessAmount, '0', 2) > 0 && $repositoryId) {
                /** @var PaymentRepository|null $foundRepository */
                $foundRepository = PaymentRepository::find($repositoryId);
                $repository = $repository ?? $foundRepository;

                if ($repository && $repository->account_id) {
                    // Create customer advance GL entry for excess (Dr. Bank, Cr. Customer Advance)
                    $this->glService->createCustomerAdvanceJournalEntry(
                        companyId: $companyId,
                        partnerId: $validated['partner_id'],
                        advanceId: $payment->id,
                        amount: $excessAmount,
                        paymentMethodAccountId: $repository->account_id,
                        date: new \DateTimeImmutable($validated['payment_date']),
                        user: $user,
                        description: "Customer advance from payment {$payment->reference}"
                    );

                    // Update payment type to indicate partial advance
                    if (bccomp($totalAllocatedForGL, '0', 2) > 0) {
                        // Has both allocated and excess - keep as DocumentPayment
                        // The advance portion is tracked via GL
                    } else {
                        // Pure advance payment (no allocations)
                        $payment->payment_type = PaymentType::Advance;
                        $payment->save();
                    }
                }
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
            'payment_number' => $payment->reference ?? 'PMT-' . substr($payment->id, 0, 8),
            'partner_id' => $payment->partner_id,
            'partner_name' => $payment->partner?->name,
            'partner_type' => $payment->partner?->type,
            'partner' => $payment->partner ? [
                'id' => $payment->partner->id,
                'name' => $payment->partner->name,
                'type' => $payment->partner->type,
            ] : null,
            'payment_method_id' => $payment->payment_method_id,
            'payment_method_name' => $payment->paymentMethod?->name,
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
            'payment_type' => $payment->payment_type?->value,
            'allocated_amount' => $payment->getAllocatedAmount(),
            'unallocated_amount' => $payment->getUnallocatedAmount(),
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
