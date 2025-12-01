<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

/**
 * Status of a fiscal period.
 */
enum PeriodStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Locked = 'locked';
}
