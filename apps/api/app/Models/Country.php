<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    public const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'currency_code',
        'currency_symbol',
        'phone_prefix',
        'date_format',
        'default_locale',
        'default_timezone',
        'is_active',
        'tax_id_label',
        'tax_id_regex',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Get the tax rates for the country.
     */
    public function taxRates(): HasMany
    {
        return $this->hasMany(CountryTaxRate::class, 'country_code', 'code');
    }
}
