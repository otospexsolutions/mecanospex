<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\CompanyDocument;
use App\Modules\Company\Domain\Enums\DocumentReviewStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 0.1.5: Tests for company_documents table.
 *
 * CompanyDocument stores uploaded documents for companies (verification documents,
 * certificates, tax registrations, etc.).
 */
class CompanyDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Account',
            'slug' => 'test-account',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ACME Garage',
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_company_document_can_be_created_with_required_fields(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax_registration.pdf',
        ]);

        $this->assertDatabaseHas('company_documents', [
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax_registration.pdf',
        ]);

        $this->assertNotNull($document->id);
        $this->assertEquals(36, strlen($document->id)); // UUID length
    }

    public function test_company_document_belongs_to_company(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'business_license',
            'file_path' => 'companies/'.$this->company->id.'/license.pdf',
        ]);

        $this->assertEquals($this->company->id, $document->company->id);
    }

    public function test_company_can_have_multiple_documents(): void
    {
        CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
        ]);

        CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'business_license',
            'file_path' => 'companies/'.$this->company->id.'/license.pdf',
        ]);

        CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'vat_certificate',
            'file_path' => 'companies/'.$this->company->id.'/vat.pdf',
        ]);

        $this->assertCount(3, $this->company->fresh()->documents);
    }

    public function test_company_document_has_file_metadata(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'original_filename' => 'my_tax_registration_2024.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertEquals('my_tax_registration_2024.pdf', $document->original_filename);
        $this->assertEquals(1024000, $document->file_size);
        $this->assertEquals('application/pdf', $document->mime_type);
    }

    public function test_company_document_has_default_status(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
        ]);

        $document->refresh();

        $this->assertEquals(DocumentReviewStatus::Pending, $document->status);
    }

    public function test_company_document_status_enum(): void
    {
        $approved = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'status' => DocumentReviewStatus::Approved,
        ]);

        $rejected = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'business_license',
            'file_path' => 'companies/'.$this->company->id.'/license.pdf',
            'status' => DocumentReviewStatus::Rejected,
            'rejection_reason' => 'Document is expired',
        ]);

        $this->assertEquals(DocumentReviewStatus::Approved, $approved->status);
        $this->assertEquals(DocumentReviewStatus::Rejected, $rejected->status);
        $this->assertEquals('Document is expired', $rejected->rejection_reason);
    }

    public function test_company_document_has_review_tracking(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'status' => DocumentReviewStatus::Approved,
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
        ]);

        $this->assertNotNull($document->reviewed_at);
        $this->assertEquals($this->user->id, $document->reviewer->id);
    }

    public function test_company_document_has_expiration_date(): void
    {
        $document = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'expires_at' => now()->addYear(),
        ]);

        $this->assertNotNull($document->expires_at);
        $this->assertFalse($document->isExpired());
    }

    public function test_expired_document_helper(): void
    {
        $expired = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'expires_at' => now()->subDay(),
        ]);

        $valid = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'business_license',
            'file_path' => 'companies/'.$this->company->id.'/license.pdf',
            'expires_at' => now()->addMonth(),
        ]);

        $noExpiry = CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'vat_certificate',
            'file_path' => 'companies/'.$this->company->id.'/vat.pdf',
            'expires_at' => null,
        ]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($valid->isExpired());
        $this->assertFalse($noExpiry->isExpired()); // null expiry = never expires
    }

    public function test_pending_documents_scope(): void
    {
        CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'tax_registration',
            'file_path' => 'companies/'.$this->company->id.'/tax.pdf',
            'status' => DocumentReviewStatus::Pending,
        ]);

        CompanyDocument::create([
            'company_id' => $this->company->id,
            'document_type' => 'business_license',
            'file_path' => 'companies/'.$this->company->id.'/license.pdf',
            'status' => DocumentReviewStatus::Approved,
        ]);

        $pending = CompanyDocument::where('status', DocumentReviewStatus::Pending)->get();

        $this->assertCount(1, $pending);
    }
}
