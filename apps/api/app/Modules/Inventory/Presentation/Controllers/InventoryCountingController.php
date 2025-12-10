<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Controllers;

use App\Modules\Company\Services\CompanyContext;
use App\Modules\Inventory\Application\Services\InventoryCountingService;
use App\Modules\Inventory\Domain\Enums\CountingStatus;
use App\Modules\Inventory\Domain\InventoryCounting;
use App\Modules\Inventory\Presentation\Requests\CreateCountingRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InventoryCountingController extends Controller
{
    public function __construct(
        private readonly CompanyContext $companyContext,
        private readonly InventoryCountingService $countingService,
    ) {}

    /**
     * Dashboard summary.
     */
    public function dashboard(): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $active = InventoryCounting::forCompany($companyId)->active()->count();
        $pendingReview = InventoryCounting::forCompany($companyId)->pendingReview()->count();
        $completedThisMonth = InventoryCounting::forCompany($companyId)
            ->where('status', CountingStatus::Finalized)
            ->whereNotNull('finalized_at')
            ->whereMonth('finalized_at', now()->month)
            ->count();
        $overdue = InventoryCounting::forCompany($companyId)
            ->active()
            ->where('scheduled_end', '<', now())
            ->count();

        $activeCounts = InventoryCounting::forCompany($companyId)
            ->active()
            ->with(['count1User', 'count2User', 'count3User', 'assignments'])
            ->orderBy('scheduled_end')
            ->take(5)
            ->get();

        $pendingReviewCounts = InventoryCounting::forCompany($companyId)
            ->pendingReview()
            ->with(['items'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'data' => [
                'summary' => [
                    'active' => $active,
                    'pending_review' => $pendingReview,
                    'completed_this_month' => $completedThisMonth,
                    'overdue' => $overdue,
                ],
                'active_counts' => $this->transformCountings($activeCounts),
                'pending_review' => $this->transformCountings($pendingReviewCounts),
            ],
        ]);
    }

    /**
     * List counting operations.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $query = InventoryCounting::forCompany($companyId)
            ->with(['count1User', 'count2User', 'count3User', 'createdBy']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('id', 'ilike', "%{$search}%");
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $countings = $query->paginate((int) $request->input('per_page', 15));

        return response()->json([
            'data' => $this->transformCountings($countings->getCollection()),
            'meta' => [
                'current_page' => $countings->currentPage(),
                'last_page' => $countings->lastPage(),
                'per_page' => $countings->perPage(),
                'total' => $countings->total(),
            ],
        ]);
    }

    /**
     * Show counting details (admin view - includes all data).
     */
    public function show(string $countingId): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();

        $counting = InventoryCounting::forCompany($companyId)
            ->with([
                'count1User',
                'count2User',
                'count3User',
                'createdBy',
                'assignments',
                'items.product',
                'items.location',
            ])
            ->findOrFail($countingId);

        return response()->json([
            'data' => $this->transformCounting($counting, true),
        ]);
    }

    /**
     * Counter view (BLIND - no theoretical quantities!).
     *
     * CRITICAL: This endpoint must NEVER return theoretical_qty or other counters' results.
     */
    public function counterView(Request $request, string $countingId): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();

        $counting = InventoryCounting::forCompany($companyId)->findOrFail($countingId);
        $userId = (string) $user->id;

        // Verify user is assigned
        if (! $counting->isUserAssigned($userId)) {
            abort(403, 'You are not assigned to this counting');
        }

        $countNumber = $counting->getUserCountNumber($userId);
        $items = $this->countingService->getItemsForCounter($counting, $user);

        // Transform items - NEVER include theoretical_qty
        $transformedItems = $items->map(function ($item) use ($countNumber) {
            $myCountColumn = "count_{$countNumber}_qty";
            $myCountAtColumn = "count_{$countNumber}_at";

            return [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                    'barcode' => $item->product->barcode ?? null,
                ],
                'location' => [
                    'id' => $item->location->id,
                    'code' => $item->location->code ?? null,
                    'name' => $item->location->name,
                ],
                'is_counted' => $item->$myCountColumn !== null,
                'my_count' => $item->$myCountColumn,
                'my_count_at' => $item->$myCountAtColumn,
                // NEVER INCLUDE: theoretical_qty, count_1_qty, count_2_qty, count_3_qty
            ];
        });

        return response()->json([
            'data' => [
                'counting' => [
                    'id' => $counting->id,
                    'status' => $counting->status->value,
                    'instructions' => $counting->instructions,
                    'deadline' => $counting->scheduled_end?->toIso8601String(),
                ],
                'my_count_number' => $countNumber,
                'items' => $transformedItems,
                'progress' => [
                    'counted' => $items->filter(fn ($i) => $i->{"count_{$countNumber}_qty"} !== null)->count(),
                    'total' => $items->count(),
                ],
            ],
        ]);
    }

    /**
     * Create counting operation.
     */
    public function store(CreateCountingRequest $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();

        $counting = $this->countingService->create(
            $request->validated(),
            $user,
            $companyId
        );

        return response()->json([
            'data' => $this->transformCounting($counting),
        ], 201);
    }

    /**
     * Activate counting.
     */
    public function activate(Request $request, string $countingId): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();

        $counting = InventoryCounting::forCompany($companyId)->findOrFail($countingId);

        $this->countingService->activate($counting, $user);

        return response()->json([
            'message' => 'Counting activated successfully',
            'data' => $this->transformCounting($counting->fresh() ?? $counting),
        ]);
    }

    /**
     * Cancel counting.
     */
    public function cancel(Request $request, string $countingId): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $counting = InventoryCounting::forCompany($companyId)->findOrFail($countingId);

        $this->countingService->cancel(
            $counting,
            (string) $request->input('reason'),
            $user
        );

        return response()->json([
            'message' => 'Counting cancelled',
        ]);
    }

    /**
     * Finalize counting.
     */
    public function finalize(Request $request, string $countingId): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();

        $counting = InventoryCounting::forCompany($companyId)->findOrFail($countingId);

        $this->countingService->finalize($counting, $user);

        return response()->json([
            'message' => 'Counting finalized successfully',
            'data' => $this->transformCounting($counting->fresh() ?? $counting),
        ]);
    }

    /**
     * Get my assigned tasks.
     */
    public function myTasks(Request $request): JsonResponse
    {
        $companyId = $this->companyContext->requireCompanyId();
        /** @var \App\Modules\Identity\Domain\User $user */
        $user = $request->user();
        $userId = (string) $user->id;

        $countings = InventoryCounting::forCompany($companyId)
            ->where(function ($query) use ($userId): void {
                $query->where('count_1_user_id', $userId)
                    ->orWhere('count_2_user_id', $userId)
                    ->orWhere('count_3_user_id', $userId);
            })
            ->whereIn('status', [
                CountingStatus::Count1InProgress,
                CountingStatus::Count2InProgress,
                CountingStatus::Count3InProgress,
            ])
            ->with(['assignments'])
            ->get();

        return response()->json([
            'data' => $countings->map(fn ($counting) => [
                'id' => $counting->id,
                'status' => $counting->status->value,
                'my_count_number' => $counting->getUserCountNumber($userId),
                'instructions' => $counting->instructions,
                'deadline' => $counting->scheduled_end?->toIso8601String(),
                'progress' => $counting->getProgress(),
            ]),
        ]);
    }

    /**
     * Transform a collection of countings for response.
     *
     * @param \Illuminate\Support\Collection<int, InventoryCounting> $countings
     * @return array<int, array<string, mixed>>
     */
    private function transformCountings($countings): array
    {
        return $countings->map(fn ($counting) => $this->transformCounting($counting))->all();
    }

    /**
     * Transform a counting for response.
     *
     * @return array<string, mixed>
     */
    private function transformCounting(InventoryCounting $counting, bool $includeItems = false): array
    {
        $data = [
            'id' => $counting->id,
            'company_id' => $counting->company_id,
            'scope_type' => $counting->scope_type->value,
            'scope_filters' => $counting->scope_filters,
            'execution_mode' => $counting->execution_mode->value,
            'status' => $counting->status->value,
            'scheduled_start' => $counting->scheduled_start?->toIso8601String(),
            'scheduled_end' => $counting->scheduled_end?->toIso8601String(),
            'requires_count_2' => $counting->requires_count_2,
            'requires_count_3' => $counting->requires_count_3,
            'allow_unexpected_items' => $counting->allow_unexpected_items,
            'instructions' => $counting->instructions,
            'created_at' => $counting->created_at?->toIso8601String(),
            'activated_at' => $counting->activated_at?->toIso8601String(),
            'finalized_at' => $counting->finalized_at?->toIso8601String(),
            'cancelled_at' => $counting->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $counting->cancellation_reason,
            'progress' => $counting->getProgress(),
            'count_1_user' => $counting->count1User ? [
                'id' => $counting->count1User->id,
                'name' => $counting->count1User->name,
            ] : null,
            'count_2_user' => $counting->count2User ? [
                'id' => $counting->count2User->id,
                'name' => $counting->count2User->name,
            ] : null,
            'count_3_user' => $counting->count3User ? [
                'id' => $counting->count3User->id,
                'name' => $counting->count3User->name,
            ] : null,
            'created_by' => $counting->createdBy ? [
                'id' => $counting->createdBy->id,
                'name' => $counting->createdBy->name,
            ] : null,
        ];

        if ($includeItems && $counting->relationLoaded('items')) {
            $data['items'] = $counting->items->map(fn ($item) => [
                'id' => $item->id,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'sku' => $item->product->sku,
                ],
                'location' => [
                    'id' => $item->location->id,
                    'name' => $item->location->name,
                ],
                'theoretical_qty' => $item->theoretical_qty,
                'count_1_qty' => $item->count_1_qty,
                'count_2_qty' => $item->count_2_qty,
                'count_3_qty' => $item->count_3_qty,
                'final_qty' => $item->final_qty,
                'variance' => $item->getVariance(),
                'resolution_method' => $item->resolution_method->value,
                'is_flagged' => $item->is_flagged,
                'flag_reason' => $item->flag_reason,
            ])->all();
        }

        return $data;
    }
}
