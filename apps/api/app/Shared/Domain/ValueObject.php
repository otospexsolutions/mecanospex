<?php

declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Base class for all value objects.
 *
 * Value objects are immutable and compared by their attributes.
 * Two value objects are equal if all their attributes are equal.
 */
abstract class ValueObject
{
    /**
     * Compare this value object with another.
     *
     * @param  ValueObject  $other  The value object to compare with
     * @return bool True if the value objects are equal
     */
    abstract public function equals(ValueObject $other): bool;

    /**
     * Get the string representation of this value object.
     */
    abstract public function __toString(): string;
}
