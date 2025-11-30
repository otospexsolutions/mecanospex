<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Compliance\Domain\AuditEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AuditService
{
    /**
     * Record a new audit event
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $tenantId,
        ?string $userId,
        string $eventType,
        string $aggregateType,
        string $aggregateId,
        array $payload = [],
        array $metadata = []
    ): AuditEvent {
        $event = new AuditEvent(
            tenantId: $tenantId,
            userId: $userId,
            eventType: $eventType,
            aggregateType: $aggregateType,
            aggregateId: $aggregateId,
            payload: $payload,
            metadata: $metadata
        );

        $event->save();

        return $event;
    }

    /**
     * Get all audit events for a tenant
     *
     * @return Collection<int, AuditEvent>
     */
    public function getEventsForTenant(string $tenantId, int $limit = 100): Collection
    {
        return AuditEvent::where('tenant_id', $tenantId)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit events for a specific aggregate
     *
     * @return Collection<int, AuditEvent>
     */
    public function getEventsForAggregate(string $aggregateType, string $aggregateId): Collection
    {
        return AuditEvent::where('aggregate_type', $aggregateType)
            ->where('aggregate_id', $aggregateId)
            ->orderBy('occurred_at')
            ->get();
    }

    /**
     * Get audit events within a date range
     *
     * @return Collection<int, AuditEvent>
     */
    public function getEventsInRange(
        string $tenantId,
        Carbon $from,
        Carbon $to,
        ?string $eventType = null
    ): Collection {
        $query = AuditEvent::where('tenant_id', $tenantId)
            ->whereBetween('occurred_at', [$from, $to]);

        if ($eventType !== null) {
            $query->where('event_type', $eventType);
        }

        return $query->orderBy('occurred_at')->get();
    }

    /**
     * Get events by type for a tenant
     *
     * @return Collection<int, AuditEvent>
     */
    public function getEventsByType(string $tenantId, string $eventType, int $limit = 100): Collection
    {
        return AuditEvent::where('tenant_id', $tenantId)
            ->where('event_type', $eventType)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Count events of a specific type within a time range
     */
    public function countEventsByType(
        string $tenantId,
        string $eventType,
        Carbon $from,
        Carbon $to
    ): int {
        return AuditEvent::where('tenant_id', $tenantId)
            ->where('event_type', $eventType)
            ->whereBetween('occurred_at', [$from, $to])
            ->count();
    }

    /**
     * Get events by user
     *
     * @return Collection<int, AuditEvent>
     */
    public function getEventsByUser(string $tenantId, string $userId, int $limit = 100): Collection
    {
        return AuditEvent::where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get();
    }
}
