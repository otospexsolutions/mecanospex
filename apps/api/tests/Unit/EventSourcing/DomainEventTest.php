<?php

declare(strict_types=1);

namespace Tests\Unit\EventSourcing;

use App\Shared\Domain\Events\DomainEvent;
use Tests\TestCase;

class DomainEventTest extends TestCase
{
    public function test_domain_event_class_exists(): void
    {
        $this->assertTrue(class_exists(DomainEvent::class));
    }

    public function test_domain_event_has_occurred_at(): void
    {
        $event = new class extends DomainEvent {};

        $this->assertNotNull($event->occurredAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredAt());
    }

    public function test_domain_event_has_aggregate_uuid(): void
    {
        $event = new class extends DomainEvent
        {
            public function __construct()
            {
                parent::__construct('test-uuid-123');
            }
        };

        $this->assertEquals('test-uuid-123', $event->aggregateRootUuid());
    }

    public function test_domain_event_has_metadata(): void
    {
        $event = new class extends DomainEvent
        {
            public function __construct()
            {
                parent::__construct('test-uuid');
            }
        };

        $event->setMetaData(['user_id' => 'user-123', 'tenant_id' => 'tenant-456']);

        $metadata = $event->metaData();
        $this->assertArrayHasKey('user_id', $metadata);
        $this->assertArrayHasKey('tenant_id', $metadata);
    }
}
