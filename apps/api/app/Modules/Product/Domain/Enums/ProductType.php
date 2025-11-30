<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Enums;

enum ProductType: string
{
    case Part = 'part';
    case Service = 'service';
    case Consumable = 'consumable';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
