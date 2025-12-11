<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain\Enums;

enum FiscalStatus: string
{
    case Draft = 'DRAFT';
    case Sealed = 'SEALED';
    case Voided = 'VOIDED';

    /**
     * Check if document is immutable (sealed or voided)
     */
    public function isImmutable(): bool
    {
        return $this === self::Sealed || $this === self::Voided;
    }

    /**
     * Check if document can be sealed
     */
    public function canBeSealed(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Check if document can be voided
     */
    public function canBeVoided(): bool
    {
        return $this === self::Sealed;
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sealed => 'Sealed',
            self::Voided => 'Voided',
        };
    }
}
