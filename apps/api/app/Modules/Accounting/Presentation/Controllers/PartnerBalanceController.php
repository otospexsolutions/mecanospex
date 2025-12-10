<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Presentation\Controllers;

use App\Modules\Accounting\Application\Services\PartnerBalanceService;
use App\Modules\Accounting\Domain\Enums\SystemAccountPurpose;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Controller for partner balance and subledger operations.
 *
 * These endpoints allow:
 * - Viewing individual partner balances from the GL
 * - Generating partner statements
 * - Viewing subledger reports (receivables, payables)
 * - Reconciling subledger against control accounts
 * - Refreshing cached partner balances
 */
class PartnerBalanceController extends Controller
{
    public function __construct(
        private readonly PartnerBalanceService $balanceService
    ) {}

    /**
     * GET /api/v1/companies/{companyId}/partners/{partnerId}/balance
     *
     * Get balance for a specific partner, optionally filtered by account purpose.
     */
    public function show(Request $request, string $companyId, string $partnerId): JsonResponse
    {
        $purpose = null;
        /** @var string|null $purposeQuery */
        $purposeQuery = $request->query('purpose');
        if ($purposeQuery !== null) {
            $purpose = SystemAccountPurpose::from($purposeQuery);
        }

        $balance = $this->balanceService->getPartnerBalance($companyId, $partnerId, $purpose);

        return response()->json([
            'data' => $balance,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{companyId}/partners/{partnerId}/statement
     *
     * Get transaction statement for a partner.
     */
    public function statement(Request $request, string $companyId, string $partnerId): JsonResponse
    {
        $purpose = null;
        /** @var string|null $purposeQuery */
        $purposeQuery = $request->query('purpose');
        if ($purposeQuery !== null) {
            $purpose = SystemAccountPurpose::from($purposeQuery);
        }

        /** @var string|null $fromDate */
        $fromDate = $request->query('from_date');
        /** @var string|null $toDate */
        $toDate = $request->query('to_date');

        $statement = $this->balanceService->getPartnerStatement(
            $companyId,
            $partnerId,
            $purpose,
            $fromDate,
            $toDate
        );

        return response()->json([
            'data' => [
                'transactions' => $statement->values(),
                'count' => $statement->count(),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{companyId}/subledger/receivables
     *
     * Get all customer receivable balances.
     */
    public function receivables(string $companyId): JsonResponse
    {
        $balances = $this->balanceService->getAllPartnerBalances(
            $companyId,
            SystemAccountPurpose::CustomerReceivable
        );

        return response()->json([
            'data' => [
                'partners' => $balances->values(),
                'total' => $balances->sum('balance'),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{companyId}/subledger/payables
     *
     * Get all supplier payable balances.
     */
    public function payables(string $companyId): JsonResponse
    {
        $balances = $this->balanceService->getAllPartnerBalances(
            $companyId,
            SystemAccountPurpose::SupplierPayable
        );

        return response()->json([
            'data' => [
                'partners' => $balances->values(),
                'total' => $balances->sum('balance'),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * GET /api/v1/companies/{companyId}/subledger/reconcile/{purpose}
     *
     * Reconcile subledger against control account.
     */
    public function reconcile(string $companyId, string $purpose): JsonResponse
    {
        $purposeEnum = SystemAccountPurpose::from($purpose);
        $result = $this->balanceService->reconcileSubledger($companyId, $purposeEnum);

        return response()->json([
            'data' => $result,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/companies/{companyId}/partners/{partnerId}/balance/refresh
     *
     * Refresh cached balance for a partner (recalculate from GL).
     */
    public function refresh(string $companyId, string $partnerId): JsonResponse
    {
        $this->balanceService->refreshPartnerBalance($companyId, $partnerId);

        $balance = $this->balanceService->getCachedOrCalculateBalance(
            $companyId,
            $partnerId,
            refreshIfStale: false
        );

        return response()->json([
            'data' => [
                'message' => 'Balance refreshed successfully',
                'balance' => $balance,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/v1/companies/{companyId}/partners/balance/refresh-all
     *
     * Refresh cached balances for all partners in a company.
     */
    public function refreshAll(string $companyId): JsonResponse
    {
        $count = $this->balanceService->refreshAllPartnerBalances($companyId);

        return response()->json([
            'data' => [
                'message' => 'All partner balances refreshed successfully',
                'partners_updated' => $count,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
