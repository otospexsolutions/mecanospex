<?php

declare(strict_types=1);

namespace Tests\Feature\Company;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Location;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 0.1.7: Tests for company_id column additions to stock tables.
 *
 * Stock tables already have location_id (which belongs to company).
 * Adding company_id for denormalized access and easier company-scoped queries.
 */
class StockCompanyIdMigrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

    private Location $location;

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
    }

    public function test_stock_levels_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('stock_levels', 'company_id'));
    }

    public function test_stock_movements_table_has_company_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('stock_movements', 'company_id'));
    }
}
