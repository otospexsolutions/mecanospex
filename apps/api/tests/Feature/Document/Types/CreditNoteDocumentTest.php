<?php

declare(strict_types=1);

namespace Tests\Feature\Document\Types;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\UserCompanyMembership;
use App\Modules\Document\Domain\Document;
use App\Modules\Document\Domain\Enums\DocumentStatus;
use App\Modules\Document\Domain\Enums\DocumentType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Enums\PartnerType;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditNoteDocumentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Company $company;

    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Company',
            'legal_name' => 'Test Company LLC',
            'tax_id' => 'TAX123',
            'country_code' => 'FR',
            'locale' => 'fr_FR',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
            'status' => \App\Modules\Company\Domain\Enums\CompanyStatus::Active,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'status' => UserStatus::Active,
        ]);
        $this->user->givePermissionTo(['credit-notes.view', 'credit-notes.create', 'credit-notes.post']);

        UserCompanyMembership::create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);

        // Set company context for the test
        app(\App\Modules\Company\Services\CompanyContext::class)->setCompanyId($this->company->id);

        $this->partner = Partner::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Test Partner',
            'type' => PartnerType::Customer,
            'email' => 'partner@example.com',
        ]);
    }

    public function test_credit_note_can_be_created(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/credit-notes', [
            'partner_id' => $this->partner->id,
            'document_date' => '2025-01-20',
            'lines' => [
                [
                    'description' => 'Refund for damaged goods',
                    'quantity' => '1.00',
                    'unit_price' => '100.00',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals('credit_note', $response->json('data.type'));
    }

    public function test_credit_note_can_reference_original_invoice(): void
    {
        $invoice = Document::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::Invoice,
            'status' => DocumentStatus::Posted,
            'document_number' => 'INV-2025-0001',
            'document_date' => '2025-01-15',
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/credit-notes', [
            'partner_id' => $this->partner->id,
            'document_date' => '2025-01-20',
            'source_document_id' => $invoice->id,
            'lines' => [
                [
                    'description' => 'Refund for INV-2025-0001',
                    'quantity' => '1.00',
                    'unit_price' => '50.00',
                ],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertEquals($invoice->id, $response->json('data.source_document_id'));
    }

    public function test_credit_note_can_be_confirmed(): void
    {
        $creditNote = Document::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::CreditNote,
            'status' => DocumentStatus::Draft,
            'document_number' => 'CN-2025-0001',
            'document_date' => '2025-01-20',
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/credit-notes/{$creditNote->id}/confirm");

        $response->assertStatus(200);
        $this->assertEquals('confirmed', $response->json('data.status'));
    }

    public function test_credit_note_can_be_posted(): void
    {
        $creditNote = Document::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::CreditNote,
            'status' => DocumentStatus::Confirmed,
            'document_number' => 'CN-2025-0001',
            'document_date' => '2025-01-20',
            'currency' => 'EUR',
            'subtotal' => '100.00',
            'total' => '100.00',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/credit-notes/{$creditNote->id}/post");

        $response->assertStatus(200);
        $this->assertEquals('posted', $response->json('data.status'));
    }

    public function test_draft_credit_note_cannot_be_posted(): void
    {
        $creditNote = Document::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'type' => DocumentType::CreditNote,
            'status' => DocumentStatus::Draft,
            'document_number' => 'CN-2025-0001',
            'document_date' => '2025-01-20',
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/v1/credit-notes/{$creditNote->id}/post");

        $response->assertStatus(422);
        $this->assertEquals('CREDIT_NOTE_NOT_CONFIRMED', $response->json('error.code'));
    }
}
