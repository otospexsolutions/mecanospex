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

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $adminUser;

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
    }

    public function test_unauthenticated_user_cannot_list_users(): void
    {
        $response = $this->getJson('/api/v1/users');

        $response->assertUnauthorized();
    }

    public function test_admin_can_list_users(): void
    {
        // Create additional users
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'roles',
                        'lastLoginAt',
                        'createdAt',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        // Should have 3 users (admin + 2 created)
        $this->assertCount(3, $response->json('data'));
    }

    public function test_users_are_paginated(): void
    {
        // Create 15 additional users
        for ($i = 1; $i <= 15; $i++) {
            User::create([
                'tenant_id' => $this->tenant->id,
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'Password1!',
                'status' => UserStatus::Active,
            ]);
        }

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users?per_page=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(16, $response->json('meta.total')); // 15 + admin
    }

    public function test_can_filter_users_by_status(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Inactive,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users?status=inactive');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('inactive@example.com', $response->json('data.0.email'));
    }

    public function test_can_search_users_by_name(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users?search=John');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('John Doe', $response->json('data.0.name'));
    }

    public function test_can_search_users_by_email(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users?search=john.doe');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('john.doe@example.com', $response->json('data.0.email'));
    }

    public function test_users_are_scoped_to_tenant(): void
    {
        // Create another tenant with a user
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant User',
            'email' => 'other@example.com',
            'password' => 'Password1!',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/users');

        $response->assertOk();
        // Should only see users from the same tenant
        $emails = collect($response->json('data'))->pluck('email')->toArray();
        $this->assertContains('admin@example.com', $emails);
        $this->assertNotContains('other@example.com', $emails);
    }

    public function test_user_without_permission_cannot_list_users(): void
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
            ->getJson('/api/v1/users');

        $response->assertForbidden();
    }
}
