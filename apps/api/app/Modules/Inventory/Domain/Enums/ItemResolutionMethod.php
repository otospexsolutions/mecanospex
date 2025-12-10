<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Domain\Enums;

enum ItemResolutionMethod: string
{
    case Pending = 'pending';
    case AutoAllMatch = 'auto_all_match';
    case AutoCountersAgree = 'auto_counters_agree';
    case ThirdCountDecisive = 'third_count_decisive';
    case ManualOverride = 'manual_override';

    public function isAutomatic(): bool
    {
        return in_array($this, [
            self::AutoAllMatch,
            self::AutoCountersAgree,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AutoAllMatch => 'All Counts Match',
            self::AutoCountersAgree => 'Counters Agree',
            self::ThirdCountDecisive => 'Third Count Decisive',
            self::ManualOverride => 'Manual Override',
        };
    }
}
