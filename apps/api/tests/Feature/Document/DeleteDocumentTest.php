<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

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

class DeleteDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Partner $customer;

    private Document $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $this->user->assignRole('admin');

        $this->customer = Partner::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'type' => PartnerType::Customer,
        ]);

        $this->quote = Document::create([
            'tenant_id' => $this->tenant->id,
            'partner_id' => $this->customer->id,
            'type' => DocumentType::Quote,
            'status' => DocumentStatus::Draft,
            'document_number' => 'QT-2025-0001',
            'document_date' => now()->toDateString(),
            'currency' => 'EUR',
        ]);
    }

    public function test_can_delete_draft_document(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$this->quote->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('documents', [
            'id' => $this->quote->id,
        ]);
    }

    public function test_cannot_delete_posted_invoice(): void
    {
        $invoice = Document::create([
            'tenant_id' => $this->tenant->id,
            'partner_id' => $this->customer->id,
            'type' => DocumentType::Invoice,
            'status' => DocumentStatus::Posted,
            'document_number' => 'INV-2025-0001',
            'document_date' => now()->toDateString(),
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/invoices/{$invoice->id}");

        $response->assertUnprocessable()
            ->assertJson([
                'error' => [
                    'code' => 'DOCUMENT_NOT_DELETABLE',
                    'message' => 'Posted documents cannot be deleted. Use cancellation instead.',
                ],
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $invoice->id,
            'deleted_at' => null,
        ]);
    }

    public function test_returns_404_for_nonexistent_document(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$fakeId}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_delete_document(): void
    {
        $response = $this->deleteJson("/api/v1/quotes/{$this->quote->id}");

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_delete_quote(): void
    {
        $viewerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $viewerUser->assignRole('viewer');

        $response = $this->actingAs($viewerUser, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$this->quote->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_document_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $otherPartner = Partner::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Customer',
            'type' => PartnerType::Customer,
        ]);

        $otherDocument = Document::create([
            'tenant_id' => $otherTenant->id,
            'partner_id' => $otherPartner->id,
            'type' => DocumentType::Quote,
            'status' => DocumentStatus::Draft,
            'document_number' => 'QT-2025-0001',
            'document_date' => now(),
            'currency' => 'EUR',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$otherDocument->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('documents', [
            'id' => $otherDocument->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleted_document_not_shown_in_list(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/quotes/{$this->quote->id}")
            ->assertNoContent();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/quotes');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
