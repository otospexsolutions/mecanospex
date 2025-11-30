<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum MovementType: string
{
    case Receipt = 'receipt';
    case Issue = 'issue';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Adjustment = 'adjustment';

    public function isInbound(): bool
    {
        return in_array($this, [self::Receipt, self::TransferIn, self::Adjustment], true);
    }

    public function isOutbound(): bool
    {
        return in_array($this, [self::Issue, self::TransferOut], true);
    }
}
