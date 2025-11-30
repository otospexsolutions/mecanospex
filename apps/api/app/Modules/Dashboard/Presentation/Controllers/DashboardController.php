<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Presentation\Controllers;

use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Treasury\Domain\Entities\Payment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $currentMonthStart = Carbon::now()->startOfMonth();
        $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Revenue calculation (posted invoices)
        $currentRevenue = Document::where('tenant_id', $tenantId)
            ->where('type', DocumentType::Invoice)
            ->where('status', DocumentStatus::Posted)
            ->where('issue_date', '>=', $currentMonthStart)
            ->sum('total_amount');

        $previousRevenue = Document::where('tenant_id', $tenantId)
            ->where('type', DocumentType::Invoice)
            ->where('status', DocumentStatus::Posted)
            ->whereBetween('issue_date', [$previousMonthStart, $previousMonthEnd])
            ->sum('total_amount');

        $revenueChange = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        // Invoice stats
        $totalInvoices = Document::where('tenant_id', $tenantId)
            ->where('type', DocumentType::Invoice)
            ->count();

        $pendingInvoices = Document::where('tenant_id', $tenantId)
            ->where('type', DocumentType::Invoice)
            ->whereIn('status', [DocumentStatus::Draft, DocumentStatus::Confirmed])
            ->count();

        $overdueInvoices = Document::where('tenant_id', $tenantId)
            ->where('type', DocumentType::Invoice)
            ->where('status', DocumentStatus::Posted)
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->count();

        // Partner stats
        $totalPartners = Partner::where('tenant_id', $tenantId)->count();

        $newPartnersThisMonth = Partner::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $currentMonthStart)
            ->count();

        // Payment stats
        $paymentsReceived = 0.0;
        $paymentsPending = 0.0;

        if (class_exists(Payment::class)) {
            try {
                $paymentsReceived = (float) DB::table('payments')
                    ->where('tenant_id', $tenantId)
                    ->where('direction', 'inbound')
                    ->where('created_at', '>=', $currentMonthStart)
                    ->sum('amount');

                // Calculate pending from unpaid posted invoices
                $paymentsPending = (float) Document::where('tenant_id', $tenantId)
                    ->where('type', DocumentType::Invoice)
                    ->where('status', DocumentStatus::Posted)
                    ->sum('total_amount') - $paymentsReceived;

                if ($paymentsPending < 0) {
                    $paymentsPending = 0;
                }
            } catch (\Exception $e) {
                // Payments table might not exist yet
            }
        }

        return response()->json([
            'data' => [
                'revenue' => [
                    'current' => (float) $currentRevenue,
                    'previous' => (float) $previousRevenue,
                    'change' => $revenueChange,
                ],
                'invoices' => [
                    'total' => $totalInvoices,
                    'pending' => $pendingInvoices,
                    'overdue' => $overdueInvoices,
                ],
                'partners' => [
                    'total' => $totalPartners,
                    'newThisMonth' => $newPartnersThisMonth,
                ],
                'payments' => [
                    'received' => $paymentsReceived,
                    'pending' => $paymentsPending,
                ],
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'request_id' => $request->header('X-Request-ID', (string) uuid_create()),
            ],
        ]);
    }
}
