<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum MembershipStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Revoked = 'revoked';
}
