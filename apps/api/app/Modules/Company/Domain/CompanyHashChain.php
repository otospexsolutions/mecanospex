<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain;

use App\Modules\Company\Domain\Enums\HashChainType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyHashChain model - maintains hash chains for fiscal documents.
 *
 * CRITICAL: Hash chains are immutable and append-only.
 * Each document type (invoice, credit note, receipt, etc.) has its own chain.
 * The chain ensures document integrity and prevents tampering.
 *
 * @property string $id UUID of the chain entry
 * @property string $company_id UUID of the company
 * @property HashChainType $chain_type Type of hash chain
 * @property int $sequence_number Sequential number within chain
 * @property string $hash SHA-256 hash of the entry
 * @property string|null $previous_hash Hash of previous entry (null for first)
 * @property string|null $document_id UUID of the related document
 * @property string|null $document_type Type of document (for reference)
 * @property string|null $payload_hash Hash of document payload
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 */
class CompanyHashChain extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_hash_chains';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'chain_type',
        'sequence_number',
        'hash',
        'previous_hash',
        'document_id',
        'document_type',
        'payload_hash',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'chain_type' => HashChainType::class,
            'sequence_number' => 'integer',
        ];
    }

    /**
     * Get the company that owns this hash chain entry.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Verify this entry's hash against its data.
     */
    public function verifyHash(string $expectedHash): bool
    {
        return hash_equals($expectedHash, $this->hash);
    }
}
