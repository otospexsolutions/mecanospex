<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum InstrumentStatus: string
{
    case Received = 'received';
    case InTransit = 'in_transit';
    case Deposited = 'deposited';
    case Clearing = 'clearing';
    case Cleared = 'cleared';
    case Bounced = 'bounced';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Collected = 'collected';

    /**
     * Check if this status allows deposit action.
     */
    public function canDeposit(): bool
    {
        return $this === self::Received;
    }

    /**
     * Check if this status allows clear action.
     */
    public function canClear(): bool
    {
        return $this === self::Deposited || $this === self::Clearing;
    }

    /**
     * Check if this status allows bounce action.
     */
    public function canBounce(): bool
    {
        return $this === self::Deposited || $this === self::Clearing;
    }

    /**
     * Check if this status allows transfer action.
     */
    public function canTransfer(): bool
    {
        return $this === self::Received || $this === self::Bounced;
    }

    /**
     * Check if this is a terminal status.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Cleared,
            self::Expired,
            self::Cancelled,
            self::Collected,
        ], true);
    }
}
