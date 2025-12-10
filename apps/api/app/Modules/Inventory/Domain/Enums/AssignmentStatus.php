<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum AssignmentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';
}
