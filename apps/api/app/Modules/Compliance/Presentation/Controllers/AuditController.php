<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Compliance\Domain\AuditEvent;
use App\Modules\Compliance\Services\AnomalyDetectionService;
use App\Modules\Compliance\Services\AuditService;
use App\Modules\Identity\Domain\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly AnomalyDetectionService $anomalyService
    ) {}

    /**
     * List audit events for the current company
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $companyId = $this->getCompanyId($request);

        $eventType = $request->query('event_type');
        $aggregateType = $request->query('aggregate_type');
        $aggregateId = $request->query('aggregate_id');
        $from = $request->query('from');
        $to = $request->query('to');

        if ($aggregateType && $aggregateId) {
            $events = $this->auditService->getEventsForAggregate(
                (string) $aggregateType,
                (string) $aggregateId
            );
        } elseif ($from && $to) {
            $events = $this->auditService->getEventsInRange(
                $companyId,
                now()->parse((string) $from),
                now()->parse((string) $to),
                $eventType ? (string) $eventType : null
            );
        } elseif ($eventType) {
            $events = $this->auditService->getEventsByType($companyId, (string) $eventType);
        } else {
            $events = $this->auditService->getEventsForCompany($companyId);
        }

        return response()->json([
            'data' => $events->map(fn (AuditEvent $event) => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'aggregate_type' => $event->aggregate_type,
                'aggregate_id' => $event->aggregate_id,
                'payload' => $event->payload,
                'metadata' => $event->metadata,
                'user_id' => $event->user_id,
                'event_hash' => $event->event_hash,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Get detected anomalies
     */
    public function anomalies(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $from = $request->query('from')
            ? now()->parse((string) $request->query('from'))
            : now()->subDay();

        $to = $request->query('to')
            ? now()->parse((string) $request->query('to'))
            : now();

        $anomalies = $this->anomalyService->detectAnomalies($companyId, $from, $to);

        return response()->json([
            'data' => collect($anomalies)->map(fn (array $anomaly) => [
                'type' => $anomaly['type'],
                'severity' => $anomaly['severity'],
                'description' => $anomaly['description'],
                'detected_at' => $anomaly['detected_at'],
                'details' => $anomaly['details'],
            ]),
        ]);
    }

    /**
     * Get the current company ID from request context.
     * Falls back to first company membership if not specified.
     */
    private function getCompanyId(Request $request): string
    {
        // Check for company_id in header (set by middleware in Phase 0.5)
        $companyId = $request->header('X-Company-Id');
        if ($companyId !== null) {
            return $companyId;
        }

        // Check for company_id in query
        $companyId = $request->query('company_id');
        if ($companyId !== null) {
            return (string) $companyId;
        }

        // Fallback: get from user's first company membership
        /** @var User $user */
        $user = $request->user();
        $membership = $user->companyMemberships()->first();

        if ($membership === null) {
            throw new \RuntimeException('User has no company membership');
        }

        return $membership->company_id;
    }
}
