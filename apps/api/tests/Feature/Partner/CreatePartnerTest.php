<?php

declare(strict_types=1);

namespace Tests\Feature\Partner;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreatePartnerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

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
    }

    public function test_name_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'type' => 'customer',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_type_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_type_must_be_valid(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
                'type' => 'invalid',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    public function test_email_format_validation(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
                'type' => 'customer',
                'email' => 'invalid-email',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_vat_number_format_for_france(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
                'type' => 'customer',
                'country_code' => 'FR',
                'vat_number' => 'INVALID123',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['vat_number']);
    }

    public function test_vat_number_format_for_tunisia(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
                'type' => 'customer',
                'country_code' => 'TN',
                'vat_number' => 'INVALID',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['vat_number']);
    }

    public function test_duplicate_vat_number_detection(): void
    {
        // First, create a partner with a VAT number
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'First Partner',
                'type' => 'customer',
                'country_code' => 'FR',
                'vat_number' => 'FR12345678901',
            ])
            ->assertCreated();

        // Try to create another partner with the same VAT number
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Second Partner',
                'type' => 'customer',
                'country_code' => 'FR',
                'vat_number' => 'FR12345678901',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['vat_number']);
    }

    public function test_successful_creation_returns_201(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'ACME Corporation',
                'type' => 'customer',
                'email' => 'contact@acme.com',
                'phone' => '+33123456789',
                'country_code' => 'FR',
                'vat_number' => 'FR12345678901',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'ACME Corporation')
            ->assertJsonPath('data.type', 'customer')
            ->assertJsonPath('data.email', 'contact@acme.com')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'email',
                    'phone',
                    'country_code',
                    'vat_number',
                    'created_at',
                ],
                'meta',
            ]);

        $this->assertDatabaseHas('partners', [
            'name' => 'ACME Corporation',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_customer_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Customer Partner',
                'type' => 'customer',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'customer');
    }

    public function test_can_create_supplier_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Supplier Partner',
                'type' => 'supplier',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'supplier');
    }

    public function test_can_create_both_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/partners', [
                'name' => 'Both Partner',
                'type' => 'both',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'both');
    }

    public function test_unauthenticated_user_cannot_create_partner(): void
    {
        $response = $this->postJson('/api/v1/partners', [
            'name' => 'Test Partner',
            'type' => 'customer',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_partner(): void
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
            ->postJson('/api/v1/partners', [
                'name' => 'Test Partner',
                'type' => 'customer',
            ]);

        $response->assertForbidden();
    }
}
