<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Reversed = 'reversed';

    /**
     * Check if this status allows reversal.
     */
    public function canReverse(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Check if this is a terminal status.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Failed,
            self::Reversed,
        ], true);
    }

    /**
     * Check if this status represents a successful payment.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Completed;
    }
}
