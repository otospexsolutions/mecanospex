<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum FeeType: string
{
    case None = 'none';
    case Fixed = 'fixed';
    case Percentage = 'percentage';
    case Mixed = 'mixed';
}
