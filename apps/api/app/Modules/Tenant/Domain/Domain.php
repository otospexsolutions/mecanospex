<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

/**
 * Custom Domain model with UUID support.
 *
 * @property string $id UUID of the domain
 * @property string $domain Domain name
 * @property string $tenant_id Tenant UUID
 * @property bool $is_primary Whether this is the primary domain
 * @property bool $is_verified Whether the domain ownership is verified
 */
class Domain extends BaseDomain
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'domain',
        'tenant_id',
        'is_primary',
        'is_verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }
}
