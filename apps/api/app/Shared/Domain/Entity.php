<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use Illuminate\Support\Str;

/**
 * Base class for all domain entities.
 *
 * Entities have identity and lifecycle. Two entities are equal if they have
 * the same identity, regardless of their attributes.
 */
abstract class Entity
{
    protected string $id;

    public function __construct(?string $id = null)
    {
        $this->id = $id ?? (string) Str::uuid();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function equals(Entity $other): bool
    {
        return $this->id === $other->id;
    }
}
