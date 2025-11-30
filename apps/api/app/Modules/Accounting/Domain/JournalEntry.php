<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Domain;

use App\Modules\Accounting\Domain\Enums\JournalEntryStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $entry_number
 * @property \Illuminate\Support\Carbon $entry_date
 * @property string|null $description
 * @property JournalEntryStatus $status
 * @property string|null $source_type
 * @property string|null $source_id
 * @property string|null $hash
 * @property string|null $previous_hash
 * @property string|null $posted_at
 * @property string|null $posted_by
 * @property string|null $reversed_at
 * @property string|null $reversed_by
 * @property string|null $reversal_entry_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read \Illuminate\Database\Eloquent\Collection<int, JournalLine> $lines
 */
class JournalEntry extends Model
{
    use HasUuids;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'entry_number',
        'entry_date',
        'description',
        'status',
        'source_type',
        'source_id',
        'hash',
        'previous_hash',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_entry_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'status' => JournalEntryStatus::class,
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return HasMany<JournalLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }
}
