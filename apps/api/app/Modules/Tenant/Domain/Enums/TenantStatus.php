<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Enums;

/**
 * Tenant lifecycle status.
 */
enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Pending = 'pending';
    case Archived = 'archived';
}
