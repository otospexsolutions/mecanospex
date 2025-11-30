<?php

declare(strict_types=1);

namespace App\Modules\Treasury\Domain;

use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Tenant\Domain\Tenant;
use App\Modules\Treasury\Domain\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Payment record for receivables and payables.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $partner_id
 * @property string $payment_method_id
 * @property string|null $instrument_id
 * @property string|null $repository_id
 * @property numeric-string $amount
 * @property string $currency
 * @property \Illuminate\Support\Carbon $payment_date
 * @property PaymentStatus $status
 * @property string|null $reference
 * @property string|null $notes
 * @property string|null $journal_entry_id
 * @property string|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Tenant $tenant
 * @property-read Partner|null $partner
 * @property-read PaymentMethod|null $paymentMethod
 * @property-read PaymentInstrument|null $instrument
 * @property-read PaymentRepository|null $repository
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PaymentAllocation> $allocations
 */
class Payment extends Model
{
    use HasUuids;

    protected $table = 'payments';

    protected $fillable = [
        'tenant_id',
        'partner_id',
        'payment_method_id',
        'instrument_id',
        'repository_id',
        'amount',
        'currency',
        'payment_date',
        'status',
        'reference',
        'notes',
        'journal_entry_id',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'status' => PaymentStatus::class,
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
     * @return BelongsTo<PaymentMethod, $this>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return BelongsTo<PaymentInstrument, $this>
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(PaymentInstrument::class);
    }

    /**
     * @return BelongsTo<PaymentRepository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(PaymentRepository::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Scope to filter by tenant.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTenant(Builder $query, string $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to filter by partner.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForPartner(Builder $query, string $partnerId): Builder
    {
        return $query->where('partner_id', $partnerId);
    }

    /**
     * Scope to filter by status.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithStatus(Builder $query, PaymentStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Get the total amount allocated to documents.
     */
    public function getAllocatedAmount(): string
    {
        /** @var numeric-string $total */
        $total = $this->allocations->sum('amount');

        return (string) $total;
    }

    /**
     * Get the unallocated amount.
     */
    public function getUnallocatedAmount(): string
    {
        /** @var numeric-string $allocatedAmount */
        $allocatedAmount = $this->getAllocatedAmount();

        return bcsub($this->amount, $allocatedAmount, 2);
    }
}
