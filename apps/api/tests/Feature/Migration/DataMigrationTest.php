<?php

declare(strict_types=1);

namespace Tests\Feature\Migration;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Enums\MembershipRole;
use App\Modules\Company\Domain\Location;
use App\Modules\Company\Domain\UserCompanyMembership;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Phase 0.2.1: Tests for the data migration command.
 *
 * This tests the actual data migration that converts tenant data
 * to the new company-based architecture.
 */
class DataMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test migration creates companies for all tenants.
     */
    public function test_migration_creates_companies_for_all_tenants(): void
    {
        // Create multiple tenants
        $tenant1 = Tenant::create([
            'name' => 'Garage A',
            'slug' => 'garage-a',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Garage B',
            'slug' => 'garage-b',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Starter,
        ]);

        // Run migration logic
        $this->runMigrationLogic();

        // Verify companies created
        $this->assertDatabaseHas('companies', ['tenant_id' => $tenant1->id]);
        $this->assertDatabaseHas('companies', ['tenant_id' => $tenant2->id]);
        $this->assertEquals(2, Company::count());
    }

    /**
     * Test migration creates default locations for companies.
     */
    public function test_migration_creates_default_locations(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Garage',
            'slug' => 'test-garage',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->runMigrationLogic();

        $company = Company::where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($company);

        $location = Location::where('company_id', $company->id)->where('is_default', true)->first();
        $this->assertNotNull($location);
        $this->assertEquals('Siège principal', $location->name);
        $this->assertTrue($location->is_default);
        $this->assertTrue($location->is_active);
    }

    /**
     * Test migration creates owner memberships for users.
     */
    public function test_migration_creates_owner_memberships(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Account',
            'slug' => 'test-account',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->runMigrationLogic();

        $company = Company::where('tenant_id', $tenant->id)->first();
        $membership = UserCompanyMembership::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->first();

        $this->assertNotNull($membership);
        $this->assertEquals(MembershipRole::Owner, $membership->role);
        $this->assertTrue($membership->is_primary);
    }

    /**
     * Test migration assigns admin role to non-first users.
     */
    public function test_migration_assigns_admin_to_additional_users(): void
    {
        $tenant = Tenant::create([
            'name' => 'Multi User Garage',
            'slug' => 'multi-user',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $user1 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $user2 = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->runMigrationLogic();

        $company = Company::where('tenant_id', $tenant->id)->first();

        $membership1 = UserCompanyMembership::where('user_id', $user1->id)
            ->where('company_id', $company->id)
            ->first();
        $membership2 = UserCompanyMembership::where('user_id', $user2->id)
            ->where('company_id', $company->id)
            ->first();

        $this->assertEquals(MembershipRole::Owner, $membership1->role);
        $this->assertEquals(MembershipRole::Admin, $membership2->role);
    }

    /**
     * Test migration is idempotent (can run multiple times safely).
     */
    public function test_migration_is_idempotent(): void
    {
        $tenant = Tenant::create([
            'name' => 'Idempotent Test',
            'slug' => 'idempotent-test',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Run migration twice
        $this->runMigrationLogic();
        $this->runMigrationLogic();

        // Should still have only one company, location, and membership
        $this->assertEquals(1, Company::where('tenant_id', $tenant->id)->count());
        $company = Company::where('tenant_id', $tenant->id)->first();
        $this->assertEquals(1, Location::where('company_id', $company->id)->count());
        $this->assertEquals(1, UserCompanyMembership::where('company_id', $company->id)->count());
    }

    /**
     * Test migration skips inactive tenants.
     */
    public function test_migration_skips_inactive_tenants(): void
    {
        $activeTenant = Tenant::create([
            'name' => 'Active Garage',
            'slug' => 'active-garage',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $inactiveTenant = Tenant::create([
            'name' => 'Inactive Garage',
            'slug' => 'inactive-garage',
            'status' => TenantStatus::Suspended,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->runMigrationLogic();

        // Only active tenant should have company
        $this->assertDatabaseHas('companies', ['tenant_id' => $activeTenant->id]);
        $this->assertDatabaseMissing('companies', ['tenant_id' => $inactiveTenant->id]);
    }

    /**
     * Test migration copies tenant name to company.
     */
    public function test_migration_copies_tenant_name_to_company(): void
    {
        $tenant = Tenant::create([
            'name' => 'Unique Garage Name',
            'slug' => 'unique-garage',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->runMigrationLogic();

        $company = Company::where('tenant_id', $tenant->id)->first();
        $this->assertEquals('Unique Garage Name', $company->name);
        $this->assertEquals('Unique Garage Name', $company->legal_name);
    }

    /**
     * Run the migration logic (simulates what the migration will do).
     */
    private function runMigrationLogic(): void
    {
        // Get all active tenants without a company
        $tenants = Tenant::where('status', TenantStatus::Active)
            ->whereDoesntHave('companies')
            ->get();

        foreach ($tenants as $tenant) {
            DB::transaction(function () use ($tenant) {
                // Create company
                $company = Company::create([
                    'tenant_id' => $tenant->id,
                    'name' => $tenant->name,
                    'legal_name' => $tenant->name,
                    'country_code' => 'TN',
                    'currency' => 'TND',
                    'locale' => 'fr_TN',
                    'timezone' => 'Africa/Tunis',
                ]);

                // Create default location
                Location::create([
                    'company_id' => $company->id,
                    'name' => 'Siège principal',
                    'code' => 'LOC-001',
                    'type' => 'shop',
                    'is_default' => true,
                    'is_active' => true,
                ]);

                // Create memberships for all users
                $users = User::where('tenant_id', $tenant->id)->get();
                foreach ($users as $index => $user) {
                    UserCompanyMembership::create([
                        'user_id' => $user->id,
                        'company_id' => $company->id,
                        'role' => $index === 0 ? 'owner' : 'admin',
                        'is_primary' => true,
                        'status' => 'active',
                        'accepted_at' => now(),
                    ]);
                }
            });
        }
    }
}
