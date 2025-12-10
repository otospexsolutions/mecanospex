<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain\Enums;

enum AllocationMethod: string
{
    case FIFO = 'fifo';
    case DUE_DATE_PRIORITY = 'due_date';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'First In First Out',
            self::DUE_DATE_PRIORITY => 'Due Date Priority (Most Overdue First)',
            self::MANUAL => 'Manual Selection',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::FIFO => 'Allocates payment to oldest invoices first based on invoice date',
            self::DUE_DATE_PRIORITY => 'Allocates payment to most overdue invoices first based on due date',
            self::MANUAL => 'User manually selects which invoices to pay',
        };
    }
}
