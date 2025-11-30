<?php

declare(strict_types=1);

namespace App\Shared\Domain\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

/**
 * Base class for all domain events.
 *
 * Domain events represent facts that have happened in the system.
 * They are immutable and should contain all the information needed
 * to understand what happened.
 */
abstract class DomainEvent extends ShouldBeStored
{
    private \DateTimeImmutable $occurredAt;

    private string $aggregateUuid;

    /**
     * @var array<string, mixed>
     */
    private array $metadata = [];

    public function __construct(string $aggregateUuid = '')
    {
        $this->aggregateUuid = $aggregateUuid;
        $this->occurredAt = new \DateTimeImmutable;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function aggregateRootUuid(): string
    {
        return $this->aggregateUuid;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function setMetaData(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function metaData(): array
    {
        return $this->metadata;
    }
}
