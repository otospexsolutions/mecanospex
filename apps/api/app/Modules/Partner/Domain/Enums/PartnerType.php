<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain\Enums;

enum PartnerType: string
{
    case Customer = 'customer';
    case Supplier = 'supplier';
    case Both = 'both';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
