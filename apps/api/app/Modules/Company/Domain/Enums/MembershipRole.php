<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain\Enums;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Manager = 'manager';
    case Accountant = 'accountant';
    case Cashier = 'cashier';
    case Technician = 'technician';
    case Viewer = 'viewer';

    /**
     * Check if this role is considered an admin role.
     */
    public function isAdminRole(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }
}
