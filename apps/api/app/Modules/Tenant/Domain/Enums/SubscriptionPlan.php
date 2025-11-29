<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain\Enums;

/**
 * Available subscription plans.
 */
enum SubscriptionPlan: string
{
    case Trial = 'trial';
    case Starter = 'starter';
    case Professional = 'professional';
    case Enterprise = 'enterprise';

    /**
     * Get the maximum number of users allowed for this plan.
     */
    public function maxUsers(): int
    {
        return match ($this) {
            self::Trial => 2,
            self::Starter => 5,
            self::Professional => 20,
            self::Enterprise => PHP_INT_MAX,
        };
    }

    /**
     * Get the storage quota in bytes for this plan.
     */
    public function storageQuotaBytes(): int
    {
        return match ($this) {
            self::Trial => 1 * 1024 * 1024 * 1024, // 1 GB
            self::Starter => 10 * 1024 * 1024 * 1024, // 10 GB
            self::Professional => 100 * 1024 * 1024 * 1024, // 100 GB
            self::Enterprise => PHP_INT_MAX,
        };
    }
}
