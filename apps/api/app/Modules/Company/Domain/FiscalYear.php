<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain;

use App\Modules\Identity\Domain\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FiscalYear model - defines fiscal year boundaries for a company.
 *
 * Fiscal years contain periods (months/quarters) that can be closed
 * for accounting purposes.
 *
 * @property string $id UUID of the fiscal year
 * @property string $company_id UUID of the company
 * @property string $name Display name (e.g., "2025")
 * @property Carbon $start_date Start date of fiscal year
 * @property Carbon $end_date End date of fiscal year
 * @property bool $is_closed Whether the year is closed
 * @property Carbon|null $closed_at When the year was closed
 * @property string|null $closed_by UUID of user who closed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read User|null $closedBy
 * @property-read Collection<int, FiscalPeriod> $periods
 */
class FiscalYear extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'fiscal_years';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns this fiscal year.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who closed this fiscal year.
     *
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Get all periods for this fiscal year.
     *
     * @return HasMany<FiscalPeriod, $this>
     */
    public function periods(): HasMany
    {
        return $this->hasMany(FiscalPeriod::class);
    }

    /**
     * Check if this fiscal year is closed.
     */
    public function isClosed(): bool
    {
        return $this->is_closed;
    }

    /**
     * Check if a date falls within this fiscal year.
     */
    public function containsDate(Carbon $date): bool
    {
        return $date->between($this->start_date, $this->end_date);
    }
}
