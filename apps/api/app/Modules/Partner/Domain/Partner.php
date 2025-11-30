<?php

declare(strict_types=1);

namespace App\Modules\Partner\Domain;

use App\Modules\Partner\Domain\Enums\PartnerType;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property PartnerType $type
 * @property string|null $code
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $country_code
 * @property string|null $vat_number
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 */
class Partner extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'code',
        'email',
        'phone',
        'country_code',
        'vat_number',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isCustomer(): bool
    {
        return $this->type === PartnerType::Customer || $this->type === PartnerType::Both;
    }

    public function isSupplier(): bool
    {
        return $this->type === PartnerType::Supplier || $this->type === PartnerType::Both;
    }

    public function getDisplayName(): string
    {
        return $this->name;
    }

    /**
     * Scope a query to only include customers.
     *
     * @param  Builder<Partner>  $query
     * @return Builder<Partner>
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->whereIn('type', [PartnerType::Customer, PartnerType::Both]);
    }

    /**
     * Scope a query to only include suppliers.
     *
     * @param  Builder<Partner>  $query
     * @return Builder<Partner>
     */
    public function scopeSuppliers(Builder $query): Builder
    {
        return $query->whereIn('type', [PartnerType::Supplier, PartnerType::Both]);
    }

    /**
     * Scope a query to only include partners for a specific tenant.
     *
     * @param  Builder<Partner>  $query
     * @return Builder<Partner>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
