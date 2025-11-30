<?php

declare(strict_types=1);

namespace App\Modules\Document\Domain;

use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Tenant\Domain\Tenant;
use App\Modules\Vehicle\Domain\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $partner_id
 * @property string|null $vehicle_id
 * @property DocumentType $type
 * @property DocumentStatus $status
 * @property string $document_number
 * @property \Illuminate\Support\Carbon $document_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $valid_until
 * @property string $currency
 * @property numeric-string|null $subtotal
 * @property numeric-string|null $discount_amount
 * @property numeric-string|null $tax_amount
 * @property numeric-string|null $total
 * @property numeric-string|null $balance_due
 * @property string|null $fiscal_hash
 * @property string|null $previous_hash
 * @property int|null $chain_sequence
 * @property string|null $notes
 * @property string|null $internal_notes
 * @property string|null $reference
 * @property string|null $source_document_id
 * @property array<string, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read Partner $partner
 * @property-read Vehicle|null $vehicle
 * @property-read Document|null $sourceDocument
 * @property-read Collection<int, DocumentLine> $lines
 *
 * @method static Builder<static> forTenant(string $tenantId)
 * @method static Builder<static> ofType(DocumentType $type)
 * @method static Builder<static> inStatus(DocumentStatus $status)
 */
class Document extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'documents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'partner_id',
        'vehicle_id',
        'type',
        'status',
        'document_number',
        'document_date',
        'due_date',
        'valid_until',
        'currency',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'balance_due',
        'fiscal_hash',
        'previous_hash',
        'chain_sequence',
        'notes',
        'internal_notes',
        'reference',
        'source_document_id',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'status' => DocumentStatus::class,
            'document_date' => 'date',
            'due_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'payload' => 'array',
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
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'source_document_id');
    }

    /**
     * @return HasMany<DocumentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(DocumentLine::class)->orderBy('line_number');
    }

    /**
     * Check if document is in draft status
     */
    public function isDraft(): bool
    {
        return $this->status === DocumentStatus::Draft;
    }

    /**
     * Check if document is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === DocumentStatus::Confirmed;
    }

    /**
     * Check if document is posted
     */
    public function isPosted(): bool
    {
        return $this->status === DocumentStatus::Posted;
    }

    /**
     * Check if document is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === DocumentStatus::Cancelled;
    }

    /**
     * Check if document can be edited
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Check if document can be deleted
     */
    public function isDeletable(): bool
    {
        return $this->status->isDeletable();
    }

    /**
     * Scope to filter documents by tenant
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter documents by type
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOfType(Builder $query, DocumentType $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter documents by status
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInStatus(Builder $query, DocumentStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Recalculate document totals from lines
     */
    public function recalculateTotals(): void
    {
        $subtotal = '0.00';
        $taxAmount = '0.00';

        foreach ($this->lines as $line) {
            $lineSubtotal = bcmul($line->quantity, $line->unit_price, 2);
            $lineTax = bcmul($lineSubtotal, bcdiv($line->tax_rate ?? '0', '100', 4), 2);

            $subtotal = bcadd($subtotal, $lineSubtotal, 2);
            $taxAmount = bcadd($taxAmount, $lineTax, 2);
        }

        $discountAmount = $this->discount_amount ?? '0.00';
        $total = bcadd(bcsub($subtotal, $discountAmount, 2), $taxAmount, 2);

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }
}
