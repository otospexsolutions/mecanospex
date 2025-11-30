<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum LocationType: string
{
    case Shop = 'shop';
    case Warehouse = 'warehouse';
    case Office = 'office';
    case Mobile = 'mobile';
}
