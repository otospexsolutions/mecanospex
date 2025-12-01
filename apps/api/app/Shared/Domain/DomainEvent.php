<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Base class for all domain events.
 *
 * Domain events capture something that happened in the domain that domain experts
 * care about. Events are immutable and named in past tense (e.g., InvoicePosted).
 *
 * IMPORTANT: Once an event class exists and has been used (even in tests):
 * - Never rename it
 * - Never change its payload structure
 * - Never delete it
 *
 * If requirements change, create a new version: InvoicePostedV2
 */
abstract class DomainEvent extends ShouldBeStored
{
    public readonly string $eventId;

    public readonly DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->eventId = (string) Str::uuid();
        $this->occurredAt = new DateTimeImmutable;
    }

    /**
     * Get the event type name.
     * Used for serialization and event store lookups.
     */
    public function eventType(): string
    {
        return static::class;
    }
}
