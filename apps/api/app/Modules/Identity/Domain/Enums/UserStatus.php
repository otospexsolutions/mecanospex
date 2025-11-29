<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Enums;

/**
 * User account status.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case PendingVerification = 'pending_verification';
}
