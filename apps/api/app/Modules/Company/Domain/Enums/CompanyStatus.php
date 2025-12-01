<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum CompanyStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';
}
