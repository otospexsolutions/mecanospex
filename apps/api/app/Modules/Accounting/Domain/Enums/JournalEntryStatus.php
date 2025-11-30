<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Domain\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Reversed = 'reversed';
}
