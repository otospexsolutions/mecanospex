<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlanLimitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly PlanLimitsService $limitsService
    ) {}

    /**
     * Get current tenant's subscription information.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        $info = $this->limitsService->getSubscriptionInfo($tenant);

        return response()->json([
            'data' => [
                'subscription' => $info['subscription'],
                'usage' => $info['usage'],
                'limits' => $info['limits'],
                'trial_days_remaining' => $info['subscription']?->trialDaysRemaining() ?? 0,
            ],
        ]);
    }
}
