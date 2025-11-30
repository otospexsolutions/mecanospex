<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain;

use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * Tenant model for AutoERP multi-tenancy.
 *
 * @property string $id UUID of the tenant
 * @property string $name Business name
 * @property string|null $legal_name Legal/registered business name
 * @property string $slug URL-friendly identifier
 * @property TenantStatus $status Tenant lifecycle status
 * @property SubscriptionPlan $plan Subscription plan
 * @property string|null $tax_id VAT/Tax identification number
 * @property string|null $registration_number Company registration number
 * @property array<string, mixed> $address Address object (street, city, postal_code, country)
 * @property string|null $phone Contact phone number
 * @property string|null $email Contact email
 * @property string|null $website Website URL
 * @property string|null $logo_path Path to logo file
 * @property string $primary_color Brand primary color (hex)
 * @property string|null $country_code ISO 3166-1 alpha-2 country code
 * @property string|null $currency_code ISO 4217 currency code
 * @property string $timezone Timezone identifier
 * @property string $date_format Date display format
 * @property string $locale Default locale
 * @property array<string, mixed> $settings Tenant-specific settings
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $subscription_ends_at
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'legal_name',
        'slug',
        'status',
        'plan',
        'tax_id',
        'registration_number',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'primary_color',
        'country_code',
        'currency_code',
        'timezone',
        'date_format',
        'locale',
        'settings',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'plan' => SubscriptionPlan::class,
            'address' => 'array',
            'settings' => 'array',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the custom columns for the tenants table.
     * These are stored in the data column by default in stancl/tenancy,
     * but we define them as actual columns for better querying.
     *
     * @return list<string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'legal_name',
            'slug',
            'status',
            'plan',
            'tax_id',
            'registration_number',
            'address',
            'phone',
            'email',
            'website',
            'logo_path',
            'primary_color',
            'country_code',
            'currency_code',
            'timezone',
            'date_format',
            'locale',
            'settings',
            'trial_ends_at',
            'subscription_ends_at',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Check if the tenant is active and can access the system.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    /**
     * Check if the tenant is in trial period.
     */
    public function isInTrial(): bool
    {
        if ($this->plan !== SubscriptionPlan::Trial) {
            return false;
        }

        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the tenant has a valid subscription.
     */
    public function hasValidSubscription(): bool
    {
        if ($this->isInTrial()) {
            return true;
        }

        return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
    }

    /**
     * Get the database name for this tenant.
     * For schema-based tenancy, this returns the schema name.
     */
    public function getDatabaseName(): string
    {
        return 'tenant_'.$this->slug;
    }
}
