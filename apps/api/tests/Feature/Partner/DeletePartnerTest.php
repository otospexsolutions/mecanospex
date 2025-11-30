<?php

declare(strict_types=1);

namespace Tests\Feature\Partner;

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

class DeletePartnerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

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

        $this->partner = Partner::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Partner To Delete',
            'type' => PartnerType::Customer,
        ]);
    }

    public function test_can_delete_partner(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/partners/{$this->partner->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('partners', [
            'id' => $this->partner->id,
        ]);
    }

    public function test_returns_404_for_nonexistent_partner(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/partners/{$fakeId}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_delete_partner(): void
    {
        $response = $this->deleteJson("/api/v1/partners/{$this->partner->id}");

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_delete_partner(): void
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
            ->deleteJson("/api/v1/partners/{$this->partner->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_partner_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $otherPartner = Partner::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Partner',
            'type' => PartnerType::Customer,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/partners/{$otherPartner->id}");

        $response->assertNotFound();

        // Ensure the partner is still there
        $this->assertDatabaseHas('partners', [
            'id' => $otherPartner->id,
            'deleted_at' => null,
        ]);
    }

    public function test_deleted_partner_not_shown_in_list(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/partners/{$this->partner->id}")
            ->assertNoContent();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/partners');

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
