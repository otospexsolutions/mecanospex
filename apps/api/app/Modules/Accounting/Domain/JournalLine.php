<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Domain;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $journal_entry_id
 * @property string $account_id
 * @property numeric-string $debit
 * @property numeric-string $credit
 * @property string|null $description
 * @property int $line_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read JournalEntry $journalEntry
 * @property-read Account $account
 */
class JournalLine extends Model
{
    use HasUuids;

    protected $table = 'journal_lines';

    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'line_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'line_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<JournalEntry, $this>
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
