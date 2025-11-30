<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain;

use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Enums\VerificationStatus;
use App\Modules\Company\Domain\Enums\VerificationTier;
use App\Modules\Tenant\Domain\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Company model - represents a legal entity.
 *
 * IMPORTANT: Company is where business data is scoped.
 * A tenant (account holder) can have multiple companies.
 * Each company has its own tax_id, country_code, compliance profile.
 *
 * @property string $id UUID of the company
 * @property string $tenant_id UUID of the owning tenant (account)
 * @property string $name Display name
 * @property string|null $legal_name Legal/registered name
 * @property string|null $code Internal company code
 * @property string $country_code ISO 3166-1 alpha-2 country code (CRITICAL!)
 * @property string|null $tax_id VAT/Tax identification number
 * @property string|null $registration_number Company registration number
 * @property string|null $vat_number VAT number (may differ from tax_id)
 * @property array<string, mixed> $legal_identifiers Country-specific identifiers
 * @property string|null $email Contact email
 * @property string|null $phone Contact phone
 * @property string|null $website Website URL
 * @property string|null $address_street Street address line 1
 * @property string|null $address_street_2 Street address line 2
 * @property string|null $address_city City
 * @property string|null $address_state State/Province
 * @property string|null $address_postal_code Postal/ZIP code
 * @property string|null $logo_path Path to logo file
 * @property string $primary_color Brand primary color (hex)
 * @property string $currency ISO 4217 currency code
 * @property string $locale Locale identifier
 * @property string $timezone Timezone identifier
 * @property string $date_format Date display format
 * @property int $fiscal_year_start_month Month fiscal year starts (1-12)
 * @property string $invoice_prefix Prefix for invoice numbers
 * @property int $invoice_next_number Next invoice number
 * @property string $quote_prefix Prefix for quote numbers
 * @property int $quote_next_number Next quote number
 * @property string $sales_order_prefix Prefix for sales order numbers
 * @property int $sales_order_next_number Next sales order number
 * @property string $purchase_order_prefix Prefix for purchase order numbers
 * @property int $purchase_order_next_number Next purchase order number
 * @property string $delivery_note_prefix Prefix for delivery note numbers
 * @property int $delivery_note_next_number Next delivery note number
 * @property string $receipt_prefix Prefix for receipt numbers
 * @property int $receipt_next_number Next receipt number
 * @property VerificationTier $verification_tier Verification tier
 * @property VerificationStatus $verification_status Verification status
 * @property Carbon|null $verification_submitted_at When verification was submitted
 * @property Carbon|null $verified_at When verification completed
 * @property string|null $verified_by UUID of verifier
 * @property string|null $verification_notes Verification notes
 * @property string|null $compliance_profile Compliance profile identifier
 * @property string|null $parent_company_id UUID of parent company (for chains)
 * @property bool $is_headquarters Whether this is headquarters
 * @property CompanyStatus $status Company status
 * @property Carbon|null $closed_at When company was closed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Tenant $tenant
 * @property-read Company|null $parentCompany
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Company> $childCompanies
 */
class Company extends Model
{
    use HasUuids;
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'companies';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'legal_name',
        'code',
        'country_code',
        'tax_id',
        'registration_number',
        'vat_number',
        'legal_identifiers',
        'email',
        'phone',
        'website',
        'address_street',
        'address_street_2',
        'address_city',
        'address_state',
        'address_postal_code',
        'logo_path',
        'primary_color',
        'currency',
        'locale',
        'timezone',
        'date_format',
        'fiscal_year_start_month',
        'invoice_prefix',
        'invoice_next_number',
        'quote_prefix',
        'quote_next_number',
        'sales_order_prefix',
        'sales_order_next_number',
        'purchase_order_prefix',
        'purchase_order_next_number',
        'delivery_note_prefix',
        'delivery_note_next_number',
        'receipt_prefix',
        'receipt_next_number',
        'verification_tier',
        'verification_status',
        'verification_submitted_at',
        'verified_at',
        'verified_by',
        'verification_notes',
        'compliance_profile',
        'parent_company_id',
        'is_headquarters',
        'status',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'legal_identifiers' => 'array',
            'is_headquarters' => 'boolean',
            'fiscal_year_start_month' => 'integer',
            'invoice_next_number' => 'integer',
            'quote_next_number' => 'integer',
            'sales_order_next_number' => 'integer',
            'purchase_order_next_number' => 'integer',
            'delivery_note_next_number' => 'integer',
            'receipt_next_number' => 'integer',
            'verification_tier' => VerificationTier::class,
            'verification_status' => VerificationStatus::class,
            'verification_submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'closed_at' => 'datetime',
            'status' => CompanyStatus::class,
        ];
    }

    /**
     * Get the tenant (account holder) that owns this company.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the parent company (for chains/franchises).
     *
     * @return BelongsTo<Company, $this>
     */
    public function parentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    /**
     * Get child companies (branches).
     *
     * @return HasMany<Company, $this>
     */
    public function childCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_company_id');
    }

    /**
     * Check if the company is active.
     */
    public function isActive(): bool
    {
        return $this->status === CompanyStatus::Active;
    }

    /**
     * Check if the company is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === VerificationStatus::Verified;
    }

    /**
     * Get formatted full address.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_street_2,
            $this->address_city,
            $this->address_state,
            $this->address_postal_code,
        ]);

        return count($parts) > 0 ? implode(', ', $parts) : null;
    }
}
