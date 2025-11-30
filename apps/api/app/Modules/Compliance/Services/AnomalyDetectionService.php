<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Domain\AuditEvent;
use Illuminate\Support\Carbon;

final class AnomalyDetectionService
{
    private const HIGH_VOID_THRESHOLD = 10;

    private const HIGH_ACTIVITY_THRESHOLD = 100;

    public function __construct(
        private readonly AuditService $auditService
    ) {}

    /**
     * Detect anomalies in audit events
     *
     * @return array<int, array{type: string, severity: string, description: string, detected_at: string, details: array<string, mixed>}>
     */
    public function detectAnomalies(string $tenantId, Carbon $from, Carbon $to): array
    {
        $anomalies = [];

        // Check for high void rate
        $voidAnomalies = $this->detectHighVoidRate($tenantId, $from, $to);
        $anomalies = array_merge($anomalies, $voidAnomalies);

        // Check for high activity rate
        $activityAnomalies = $this->detectHighActivityRate($tenantId, $from, $to);
        $anomalies = array_merge($anomalies, $activityAnomalies);

        // Check for unusual patterns
        $patternAnomalies = $this->detectUnusualPatterns($tenantId, $from, $to);
        $anomalies = array_merge($anomalies, $patternAnomalies);

        return $anomalies;
    }

    /**
     * Detect activity outside business hours
     *
     * @return array<int, AuditEvent>
     */
    public function detectAfterHoursActivity(
        string $tenantId,
        int $businessHoursStart = 8,
        int $businessHoursEnd = 20
    ): array {
        // Fetch all recent events and filter in PHP for database agnostic behavior
        $events = AuditEvent::where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->limit(500)
            ->get();

        return $events->filter(function ($event) use ($businessHoursStart, $businessHoursEnd) {
            $hour = (int) $event->occurred_at->format('H');

            return $hour < $businessHoursStart || $hour >= $businessHoursEnd;
        })
            ->take(100)
            ->values()
            ->all();
    }

    /**
     * Detect high void rate
     *
     * @return array<int, array{type: string, severity: string, description: string, detected_at: string, details: array<string, mixed>}>
     */
    private function detectHighVoidRate(string $tenantId, Carbon $from, Carbon $to): array
    {
        $voidCount = $this->auditService->countEventsByType(
            $tenantId,
            'document.voided',
            $from,
            $to
        );

        if ($voidCount >= self::HIGH_VOID_THRESHOLD) {
            return [[
                'type' => 'high_void_rate',
                'severity' => $voidCount >= self::HIGH_VOID_THRESHOLD * 2 ? 'critical' : 'warning',
                'description' => "High number of voided documents detected: {$voidCount} voids in the period",
                'detected_at' => now()->toIso8601String(),
                'details' => [
                    'void_count' => $voidCount,
                    'threshold' => self::HIGH_VOID_THRESHOLD,
                    'period_start' => $from->toIso8601String(),
                    'period_end' => $to->toIso8601String(),
                ],
            ]];
        }

        return [];
    }

    /**
     * Detect high activity rate (potential automated attacks)
     *
     * @return array<int, array{type: string, severity: string, description: string, detected_at: string, details: array<string, mixed>}>
     */
    private function detectHighActivityRate(string $tenantId, Carbon $from, Carbon $to): array
    {
        $totalCount = AuditEvent::where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        // Calculate events per minute
        $minutes = max(1, $from->diffInMinutes($to));
        $eventsPerMinute = $totalCount / $minutes;

        if ($eventsPerMinute >= self::HIGH_ACTIVITY_THRESHOLD) {
            return [[
                'type' => 'high_activity_rate',
                'severity' => 'warning',
                'description' => sprintf('Unusually high activity rate detected: %.2f events/minute', $eventsPerMinute),
                'detected_at' => now()->toIso8601String(),
                'details' => [
                    'events_per_minute' => $eventsPerMinute,
                    'total_events' => $totalCount,
                    'period_minutes' => $minutes,
                ],
            ]];
        }

        return [];
    }

    /**
     * Detect unusual patterns in event sequences
     *
     * @return array<int, array{type: string, severity: string, description: string, detected_at: string, details: array<string, mixed>}>
     */
    private function detectUnusualPatterns(string $tenantId, Carbon $from, Carbon $to): array
    {
        $anomalies = [];

        // Check for repeated identical actions (potential automation/abuse)
        /** @var \Illuminate\Support\Collection<int, object{event_type: string, user_id: string|null, count: int}> $repeatedActions */
        $repeatedActions = AuditEvent::where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw('event_type, user_id, COUNT(*) as count')
            ->groupBy('event_type', 'user_id')
            ->having('count', '>', 50)
            ->get();

        foreach ($repeatedActions as $action) {
            /** @var object{event_type: string, user_id: string|null, count: int} $action */
            $anomalies[] = [
                'type' => 'repeated_action',
                'severity' => 'info',
                'description' => "Repeated action detected: {$action->event_type} ({$action->count} times)",
                'detected_at' => now()->toIso8601String(),
                'details' => [
                    'event_type' => $action->event_type,
                    'user_id' => $action->user_id,
                    'count' => $action->count,
                ],
            ];
        }

        return $anomalies;
    }
}
