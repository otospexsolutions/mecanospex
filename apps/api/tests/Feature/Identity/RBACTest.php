<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\UserCompanyMembership;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RBACTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

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

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Company',
            'legal_name' => 'Test Company LLC',
            'tax_id' => 'TAX123',
            'country_code' => 'FR',
            'locale' => 'fr_FR',
            'timezone' => 'Europe/Paris',
            'currency' => 'EUR',
            'status' => CompanyStatus::Active,
        ]);

        // Set the tenant context for permissions (teams feature)
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);

        // Seed roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $this->adminUser->assignRole('admin');

        UserCompanyMembership::create([
            'user_id' => $this->adminUser->id,
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);
    }

    public function test_roles_are_created_by_seeder(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'manager']);
        $this->assertDatabaseHas('roles', ['name' => 'cashier']);
        $this->assertDatabaseHas('roles', ['name' => 'viewer']);
        $this->assertDatabaseHas('roles', ['name' => 'technician']);
        $this->assertDatabaseHas('roles', ['name' => 'accountant']);
    }

    public function test_permissions_are_created_by_seeder(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'partners.view']);
        $this->assertDatabaseHas('permissions', ['name' => 'invoices.post']);
        $this->assertDatabaseHas('permissions', ['name' => 'payments.create']);
    }

    public function test_admin_role_has_all_permissions(): void
    {
        $adminRole = Role::findByName('admin', 'sanctum');
        $allPermissions = Permission::all();

        $this->assertEquals($allPermissions->count(), $adminRole->permissions->count());
    }

    public function test_user_can_be_assigned_role(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $user->assignRole('cashier');

        $this->assertTrue($user->hasRole('cashier'));
        $this->assertTrue($user->hasPermissionTo('invoices.view'));
        $this->assertFalse($user->hasPermissionTo('invoices.post'));
    }

    public function test_user_can_have_multiple_roles(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Multi Role User',
            'email' => 'multi@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $user->assignRole(['cashier', 'technician']);

        $this->assertTrue($user->hasRole('cashier'));
        $this->assertTrue($user->hasRole('technician'));
        $this->assertTrue($user->hasPermissionTo('invoices.view'));
        $this->assertTrue($user->hasPermissionTo('work-orders.complete'));
    }

    public function test_authenticated_user_can_list_roles(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'permissions'],
                ],
                'meta',
            ]);
    }

    public function test_authenticated_user_can_list_permissions(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'partners',
                    'products',
                    'invoices',
                ],
                'meta',
            ]);
    }

    public function test_admin_can_assign_role_to_user(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/users/{$user->id}/roles", [
                'role' => 'manager',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.message', "Role 'manager' assigned to user");

        $user->refresh();
        $this->assertTrue($user->hasRole('manager'));
    }

    public function test_admin_can_remove_role_from_user(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Role User',
            'email' => 'role@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $user->assignRole('manager');

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/users/{$user->id}/roles", [
                'role' => 'manager',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.message', "Role 'manager' removed from user");

        $user->refresh();
        $this->assertFalse($user->hasRole('manager'));
    }

    public function test_can_get_user_roles(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Check User',
            'email' => 'check@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $user->assignRole(['cashier', 'technician']);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/users/{$user->id}/roles");

        $response->assertOk()
            ->assertJsonPath('data.user_id', $user->id);

        $roles = $response->json('data.roles');
        $this->assertContains('cashier', $roles);
        $this->assertContains('technician', $roles);
    }

    public function test_cashier_has_limited_permissions(): void
    {
        $cashier = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $cashier->assignRole('cashier');

        // Cashier can view invoices
        $this->assertTrue($cashier->hasPermissionTo('invoices.view'));
        $this->assertTrue($cashier->hasPermissionTo('invoices.create'));
        $this->assertTrue($cashier->hasPermissionTo('invoices.print'));

        // Cashier cannot post invoices (accounting action)
        $this->assertFalse($cashier->hasPermissionTo('invoices.post'));

        // Cashier cannot manage users
        $this->assertFalse($cashier->hasPermissionTo('users.create'));
        $this->assertFalse($cashier->hasPermissionTo('roles.manage'));
    }

    public function test_accountant_has_financial_permissions(): void
    {
        $accountant = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Accountant',
            'email' => 'accountant@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $accountant->assignRole('accountant');

        // Accountant can post invoices
        $this->assertTrue($accountant->hasPermissionTo('invoices.post'));

        // Accountant can manage journal entries
        $this->assertTrue($accountant->hasPermissionTo('journal.view'));
        $this->assertTrue($accountant->hasPermissionTo('journal.create'));
        $this->assertTrue($accountant->hasPermissionTo('journal.post'));

        // Accountant can clear instruments
        $this->assertTrue($accountant->hasPermissionTo('instruments.clear'));

        // Accountant cannot create invoices (sales action)
        $this->assertFalse($accountant->hasPermissionTo('invoices.create'));
    }
}
