<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Spatie\EventSourcing\AggregateRoots\AggregateRoot as SpatieAggregateRoot;

/**
 * Base class for all aggregate roots.
 *
 * Aggregates are clusters of domain objects that are treated as a single unit
 * for data changes. External references should only point to the aggregate root.
 *
 * This class extends Spatie's AggregateRoot to provide event sourcing capabilities.
 */
abstract class AggregateRoot extends SpatieAggregateRoot
{
    // Extend with custom functionality as needed
}
