<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Location;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 0.1.6: Tests for company_id column additions to existing tables.
 *
 * This migration adds company_id (and location_id where appropriate) to tables
 * that were previously scoped only to tenant. The new architecture scopes
 * business data to Company, not Tenant.
 */
class CompanyIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

    private Location $location;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Account',
            'slug' => 'test-account',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $this->company = Company::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'ACME Garage',
            'country_code' => 'TN',
            'currency' => 'TND',
            'locale' => 'fr_TN',
            'timezone' => 'Africa/Tunis',
        ]);

        $this->location = Location::create([
            'company_id' => $this->company->id,
            'name' => 'Main Shop',
            'type' => 'shop',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_partners_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('partners', 'company_id'));
    }

    public function test_products_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'company_id'));
    }

    public function test_vehicles_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('vehicles', 'company_id'));
    }

    public function test_documents_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('documents', 'company_id'));
    }

    public function test_documents_table_has_location_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('documents', 'location_id'));
    }

    public function test_accounts_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('accounts', 'company_id'));
    }

    public function test_journal_entries_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('journal_entries', 'company_id'));
    }

    public function test_payment_methods_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('payment_methods', 'company_id'));
    }

    public function test_payment_repositories_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('payment_repositories', 'company_id'));
    }

    public function test_payment_instruments_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('payment_instruments', 'company_id'));
    }

    public function test_payments_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('payments', 'company_id'));
    }
}
