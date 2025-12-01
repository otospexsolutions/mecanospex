<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain;

use App\Modules\Company\Domain\Enums\SequenceType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanySequence model - manages document number sequences.
 *
 * Each company has sequences for different document types (invoices, quotes, etc.).
 * Sequences are atomic and gap-free within each type.
 *
 * @property string $id UUID of the sequence
 * @property string $company_id UUID of the company
 * @property SequenceType $sequence_type Type of sequence
 * @property string $prefix Prefix for document numbers
 * @property int $current_number Current sequence number
 * @property string|null $format Number format pattern
 * @property int $reset_yearly Whether to reset yearly
 * @property int|null $last_reset_year Year of last reset
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 */
class CompanySequence extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_sequences';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'sequence_type',
        'prefix',
        'current_number',
        'format',
        'reset_yearly',
        'last_reset_year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence_type' => SequenceType::class,
            'current_number' => 'integer',
            'reset_yearly' => 'boolean',
            'last_reset_year' => 'integer',
        ];
    }

    /**
     * Get the company that owns this sequence.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the next number in the sequence (does not increment).
     */
    public function getNextNumber(): int
    {
        return $this->current_number + 1;
    }

    /**
     * Format a number using this sequence's format.
     */
    public function formatNumber(int $number): string
    {
        $format = $this->format ?? '{prefix}-{number:05d}';
        $year = date('Y');

        $formatted = str_replace('{prefix}', $this->prefix, $format);
        $formatted = str_replace('{year}', $year, $formatted);

        // Handle padded numbers like {number:05d}
        if (preg_match('/\{number:(\d+)d\}/', $formatted, $matches)) {
            $padding = (int) $matches[1];
            $paddedNumber = str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
            $formatted = preg_replace('/\{number:\d+d\}/', $paddedNumber, $formatted);
        } else {
            $formatted = str_replace('{number}', (string) $number, $formatted);
        }

        return $formatted ?? '';
    }
}
