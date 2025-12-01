<?php

declare(strict_types=1);

namespace App\Modules\Company\Domain;

use App\Modules\Company\Domain\Enums\DocumentReviewStatus;
use App\Modules\Identity\Domain\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompanyDocument model - stores uploaded documents for companies.
 *
 * Documents include verification documents, certificates, tax registrations, etc.
 *
 * @property string $id UUID of the document
 * @property string $company_id UUID of the company
 * @property string $document_type Type of document (tax_registration, business_license, etc.)
 * @property string $file_path Storage path to the file
 * @property string|null $original_filename Original filename when uploaded
 * @property int|null $file_size File size in bytes
 * @property string|null $mime_type MIME type of the file
 * @property DocumentReviewStatus $status Review status
 * @property Carbon|null $reviewed_at When the document was reviewed
 * @property string|null $reviewed_by UUID of the reviewer
 * @property string|null $rejection_reason Reason if rejected
 * @property Carbon|null $expires_at Document expiration date
 * @property Carbon $uploaded_at When the document was uploaded
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Company $company
 * @property-read User|null $reviewer
 */
class CompanyDocument extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'document_type',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
        'status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'expires_at',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentReviewStatus::class,
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
            'expires_at' => 'date',
            'uploaded_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns this document.
     *
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who reviewed this document.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Check if the document is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if the document is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === DocumentReviewStatus::Approved;
    }

    /**
     * Check if the document is pending review.
     */
    public function isPending(): bool
    {
        return $this->status === DocumentReviewStatus::Pending;
    }
}
