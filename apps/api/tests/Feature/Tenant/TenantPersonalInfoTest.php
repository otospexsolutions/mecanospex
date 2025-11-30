<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 0.1.1: Tests for personal info columns on tenants table.
 *
 * Tenant = Account/Person (subscription holder), NOT a company.
 * Personal info columns: first_name, last_name, preferred_locale
 */
class TenantPersonalInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created_with_personal_info_columns(): void
    {
        $tenant = Tenant::create([
            'name' => 'John Doe Account',
            'slug' => 'john-doe',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'preferred_locale' => 'fr',
        ]);

        $this->assertDatabaseHas('tenants', [
            'slug' => 'john-doe',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'preferred_locale' => 'fr',
        ]);

        $this->assertEquals('John', $tenant->first_name);
        $this->assertEquals('Doe', $tenant->last_name);
        $this->assertEquals('fr', $tenant->preferred_locale);
    }

    public function test_tenant_personal_info_columns_have_defaults(): void
    {
        $tenant = Tenant::create([
            'name' => 'Default Test',
            'slug' => 'default-test',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
        ]);

        // Refresh from database to get default values
        $tenant->refresh();

        // first_name and last_name should be nullable
        $this->assertNull($tenant->first_name);
        $this->assertNull($tenant->last_name);

        // preferred_locale should default to 'fr'
        $this->assertEquals('fr', $tenant->preferred_locale);
    }

    public function test_tenant_full_name_accessor(): void
    {
        $tenant = Tenant::create([
            'name' => 'Full Name Test',
            'slug' => 'full-name-test',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Jane Smith', $tenant->full_name);
    }

    public function test_tenant_full_name_accessor_with_only_first_name(): void
    {
        $tenant = Tenant::create([
            'name' => 'First Only Test',
            'slug' => 'first-only-test',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
            'first_name' => 'Jane',
        ]);

        $this->assertEquals('Jane', $tenant->full_name);
    }

    public function test_tenant_full_name_accessor_when_both_null(): void
    {
        $tenant = Tenant::create([
            'name' => 'No Name Test',
            'slug' => 'no-name-test',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
        ]);

        $this->assertNull($tenant->full_name);
    }
}
