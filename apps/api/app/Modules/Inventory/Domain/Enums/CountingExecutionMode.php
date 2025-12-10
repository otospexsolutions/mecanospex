<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum CountingExecutionMode: string
{
    case Parallel = 'parallel';
    case Sequential = 'sequential';
}
