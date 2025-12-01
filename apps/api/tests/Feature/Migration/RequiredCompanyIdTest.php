<?php

declare(strict_types=1);

namespace Tests\Feature\Migration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Phase 0.2.2: Tests that company_id is required on all tables after migration.
 *
 * After data migration, company_id should be non-null on all business tables.
 */
class RequiredCompanyIdTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test partners table has required company_id.
     */
    public function test_partners_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('partners');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        // After the make-company-id-required migration, nullable should be false
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test products table has required company_id.
     */
    public function test_products_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('products');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test vehicles table has required company_id.
     */
    public function test_vehicles_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('vehicles');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test documents table has required company_id.
     */
    public function test_documents_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('documents');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test accounts table has required company_id.
     */
    public function test_accounts_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('accounts');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test journal_entries table has required company_id.
     */
    public function test_journal_entries_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('journal_entries');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test payment_methods table has required company_id.
     */
    public function test_payment_methods_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('payment_methods');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test payment_repositories table has required company_id.
     */
    public function test_payment_repositories_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('payment_repositories');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test payment_instruments table has required company_id.
     */
    public function test_payment_instruments_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('payment_instruments');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test payments table has required company_id.
     */
    public function test_payments_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('payments');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test stock_levels table has required company_id.
     */
    public function test_stock_levels_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('stock_levels');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }

    /**
     * Test stock_movements table has required company_id.
     */
    public function test_stock_movements_table_company_id_is_required(): void
    {
        $columns = Schema::getColumns('stock_movements');
        $companyIdColumn = collect($columns)->firstWhere('name', 'company_id');

        $this->assertNotNull($companyIdColumn);
        $this->assertFalse($companyIdColumn['nullable']);
    }
}
