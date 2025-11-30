<?php

declare(strict_types=1);

namespace Tests\Feature\Identity\UserManagement;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $adminUser;

    private User $targetUser;

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

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);
        $this->adminUser->assignRole('admin');

        $this->targetUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Target User',
            'email' => 'target@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);
        $this->targetUser->assignRole('operator');
    }

    public function test_unauthenticated_user_cannot_delete_user(): void
    {
        $response = $this->deleteJson("/api/v1/users/{$this->targetUser->id}");

        $response->assertUnauthorized();
    }

    public function test_admin_can_delete_user(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->targetUser->id}");

        $response->assertOk()
            ->assertJsonPath('data.message', 'User deleted successfully');

        // User should be soft-deleted (status changed to inactive)
        $this->assertDatabaseHas('users', [
            'id' => $this->targetUser->id,
            'status' => UserStatus::Inactive->value,
        ]);
    }

    public function test_cannot_delete_user_from_different_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $otherUser = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$otherUser->id}");

        $response->assertNotFound();

        // User should still be active
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'status' => UserStatus::Active->value,
        ]);
    }

    public function test_user_without_permission_cannot_delete_user(): void
    {
        $viewerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);
        $viewerUser->assignRole('viewer');

        $response = $this->actingAs($viewerUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->targetUser->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->adminUser->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'CANNOT_DELETE_SELF');
    }

    public function test_delete_returns_404_for_nonexistent_user(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson('/api/v1/users/00000000-0000-0000-0000-000000000000');

        $response->assertNotFound();
    }

    public function test_delete_is_logged_to_audit_trail(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->targetUser->id}");

        $response->assertOk();

        $this->assertDatabaseHas('audit_events', [
            'event_type' => 'user.deleted',
            'aggregate_type' => 'user',
            'aggregate_id' => $this->targetUser->id,
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_deleted_user_tokens_are_revoked(): void
    {
        // Create a token for the target user
        $this->targetUser->createToken('test-token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->targetUser->id,
            'tokenable_type' => User::class,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$this->targetUser->id}");

        $response->assertOk();

        // Tokens should be deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->targetUser->id,
            'tokenable_type' => User::class,
        ]);
    }
}
