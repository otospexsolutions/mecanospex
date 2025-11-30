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
     * List audit events for the current tenant
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tenantId = $user->tenant_id;

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
                $tenantId,
                now()->parse((string) $from),
                now()->parse((string) $to),
                $eventType ? (string) $eventType : null
            );
        } elseif ($eventType) {
            $events = $this->auditService->getEventsByType($tenantId, (string) $eventType);
        } else {
            $events = $this->auditService->getEventsForTenant($tenantId);
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
        /** @var User $user */
        $user = $request->user();
        $tenantId = $user->tenant_id;

        $from = $request->query('from')
            ? now()->parse((string) $request->query('from'))
            : now()->subDay();

        $to = $request->query('to')
            ? now()->parse((string) $request->query('to'))
            : now();

        $anomalies = $this->anomalyService->detectAnomalies($tenantId, $from, $to);

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
}
