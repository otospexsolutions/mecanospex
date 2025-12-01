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
use Tests\TestCase;

/**
 * Phase 0.2: Tests for tenant-to-company data migration.
 *
 * This migration converts existing tenant data to the new architecture:
 * - Creates a company for each tenant
 * - Creates a default location for each company
 * - Creates owner membership for each user
 * - Updates business records with company_id
 */
class TenantToCompanyMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that we can create a company from tenant data.
     */
    public function test_can_create_company_from_tenant(): void
    {
        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'ACME Corporation',
            'slug' => 'acme-corp',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        // Create company with tenant's data
        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'legal_name' => $tenant->name,
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'tenant_id' => $tenant->id,
            'name' => 'ACME Corporation',
        ]);
    }

    /**
     * Test that we can create a default location for a company.
     */
    public function test_can_create_default_location_for_company(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Garage',
            'slug' => 'test-garage',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $location = Location::create([
            'company_id' => $company->id,
            'name' => 'Siège principal',
            'code' => 'LOC-001',
            'type' => 'shop',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'company_id' => $company->id,
            'is_default' => true,
        ]);
    }

    /**
     * Test that we can create owner membership for a user.
     */
    public function test_can_create_owner_membership(): void
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

        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $membership = UserCompanyMembership::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => 'owner',
            'is_primary' => true,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->assertDatabaseHas('user_company_memberships', [
            'id' => $membership->id,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'role' => 'owner',
        ]);
    }

    /**
     * Test that we can update tenant personal info.
     */
    public function test_can_update_tenant_with_personal_info(): void
    {
        $tenant = Tenant::create([
            'name' => 'John Doe',
            'slug' => 'john-doe',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        // Split name into first/last
        $nameParts = explode(' ', $tenant->name, 2);

        $tenant->update([
            'first_name' => $nameParts[0] ?? $tenant->name,
            'last_name' => $nameParts[1] ?? '',
            'preferred_locale' => 'fr',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'preferred_locale' => 'fr',
        ]);
    }

    /**
     * Test full migration scenario.
     */
    public function test_full_migration_scenario(): void
    {
        // Create tenant with user
        $tenant = Tenant::create([
            'name' => 'Test Auto',
            'slug' => 'test-auto',
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

        // Create company
        $company = Company::create([
            'tenant_id' => $tenant->id,
            'name' => $tenant->name,
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        // Create default location
        $location = Location::create([
            'company_id' => $company->id,
            'name' => 'Siège principal',
            'code' => 'LOC-001',
            'type' => 'shop',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create memberships (first user = owner, rest = admin)
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

        // Verify
        $this->assertCount(1, $tenant->companies);
        $this->assertCount(1, $company->locations);
        $this->assertCount(2, $company->memberships);
        $this->assertEquals(MembershipRole::Owner, $company->memberships()->where('user_id', $user1->id)->first()->role);
        $this->assertEquals(MembershipRole::Admin, $company->memberships()->where('user_id', $user2->id)->first()->role);
    }
}
