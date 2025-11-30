<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Import\Domain\Enums\ImportType;
use App\Modules\Import\Services\MigrationWizardService;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Product\Domain\Product;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MigrationWizardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

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
    }

    // === Service Tests ===

    public function test_migration_wizard_service_exists(): void
    {
        $this->assertTrue(class_exists(MigrationWizardService::class));
    }

    public function test_get_recommended_import_order(): void
    {
        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        $order = $wizard->getRecommendedImportOrder();

        // Partners should come before products (for supplier references)
        $partnerIndex = array_search(ImportType::Partners, $order);
        $productIndex = array_search(ImportType::Products, $order);

        $this->assertLessThan($productIndex, $partnerIndex);

        // Products before stock levels
        $stockIndex = array_search(ImportType::StockLevels, $order);
        $this->assertLessThan($stockIndex, $productIndex);
    }

    public function test_check_dependencies_for_import_type(): void
    {
        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        // Partners have no dependencies
        $partnerDeps = $wizard->checkDependencies($this->tenant->id, ImportType::Partners);
        $this->assertTrue($partnerDeps['can_import']);
        $this->assertEmpty($partnerDeps['missing_dependencies']);

        // Products have no hard dependencies
        $productDeps = $wizard->checkDependencies($this->tenant->id, ImportType::Products);
        $this->assertTrue($productDeps['can_import']);

        // Stock levels require products and locations
        $stockDeps = $wizard->checkDependencies($this->tenant->id, ImportType::StockLevels);
        $this->assertFalse($stockDeps['can_import']);
        $this->assertContains('products', $stockDeps['missing_dependencies']);
    }

    public function test_check_dependencies_passes_with_data(): void
    {
        // Create products and locations
        Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'type' => \App\Modules\Product\Domain\Enums\ProductType::Part,
        ]);

        \App\Modules\Inventory\Domain\Location::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Warehouse',
            'code' => 'WH-001',
        ]);

        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        $stockDeps = $wizard->checkDependencies($this->tenant->id, ImportType::StockLevels);
        $this->assertTrue($stockDeps['can_import']);
    }

    public function test_get_column_mapping_suggestions(): void
    {
        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        $sourceHeaders = ['customer_name', 'email_address', 'partner_type', 'phone_number'];
        $suggestions = $wizard->suggestColumnMapping(ImportType::Partners, $sourceHeaders);

        $this->assertArrayHasKey('name', $suggestions);
        $this->assertArrayHasKey('email', $suggestions);
        $this->assertArrayHasKey('type', $suggestions);
    }

    public function test_generate_import_template(): void
    {
        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        $template = $wizard->generateTemplate(ImportType::Partners);

        $this->assertStringContainsString('name', $template);
        $this->assertStringContainsString('type', $template);
        $this->assertStringContainsString('email', $template);
    }

    public function test_get_migration_status(): void
    {
        // Create some data
        Partner::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Partner',
            'type' => \App\Modules\Partner\Domain\Enums\PartnerType::Customer,
        ]);

        /** @var MigrationWizardService $wizard */
        $wizard = app(MigrationWizardService::class);

        $status = $wizard->getMigrationStatus($this->tenant->id);

        $this->assertArrayHasKey('partners', $status);
        $this->assertEquals(1, $status['partners']['count']);
        $this->assertTrue($status['partners']['has_data']);
    }

    // === API Tests ===

    public function test_api_returns_recommended_import_order(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/migration-wizard/order');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'type',
                        'label',
                        'description',
                    ],
                ],
            ]);
    }

    public function test_api_checks_dependencies(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/migration-wizard/dependencies/partners');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'can_import',
                    'missing_dependencies',
                    'warnings',
                ],
            ]);
    }

    public function test_api_returns_column_suggestions(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/migration-wizard/suggest-mapping', [
                'type' => 'partners',
                'headers' => ['customer_name', 'email_address', 'customer_type'],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'suggestions',
                    'unmapped_source',
                    'unmapped_target',
                ],
            ]);
    }

    public function test_api_generates_template(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/migration-wizard/template/partners');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_api_returns_migration_status(): void
    {
        Partner::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Partner',
            'type' => \App\Modules\Partner\Domain\Enums\PartnerType::Customer,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/migration-wizard/status');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'partners' => ['count', 'has_data'],
                    'products' => ['count', 'has_data'],
                    'stock_levels' => ['count', 'has_data'],
                    'accounts' => ['count', 'has_data'],
                ],
            ]);
    }

    public function test_unauthorized_user_cannot_access_wizard(): void
    {
        $response = $this->getJson('/api/v1/migration-wizard/order');

        $response->assertUnauthorized();
    }
}
