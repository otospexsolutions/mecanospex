<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Services\CompanyContext;
use App\Modules\Product\Domain\Enums\ProductType;
use App\Modules\Product\Domain\Product;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductEntityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Company $company;

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
            'status' => \App\Modules\Company\Domain\Enums\CompanyStatus::Active,
        ]);

        app(CompanyContext::class)->setCompanyId($this->company->id);
    }

    public function test_product_has_uuid_primary_key(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TST-001',
            'type' => ProductType::Part,
        ]);

        $this->assertIsString($product->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $product->id
        );
    }

    public function test_product_belongs_to_tenant(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TST-001',
            'type' => ProductType::Part,
        ]);

        $this->assertEquals($this->tenant->id, $product->tenant_id);
        $this->assertEquals($this->tenant->id, $product->tenant->id);
    }

    public function test_product_type_is_enum(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'TST-001',
            'type' => ProductType::Part,
        ]);

        $this->assertInstanceOf(ProductType::class, $product->type);
        $this->assertEquals(ProductType::Part, $product->type);
    }

    public function test_product_can_be_part(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Part Product',
            'sku' => 'PRT-001',
            'type' => ProductType::Part,
        ]);

        $this->assertTrue($product->isPart());
        $this->assertFalse($product->isService());
    }

    public function test_product_can_be_service(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Service Product',
            'sku' => 'SVC-001',
            'type' => ProductType::Service,
        ]);

        $this->assertFalse($product->isPart());
        $this->assertTrue($product->isService());
    }

    public function test_product_can_be_consumable(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Consumable Product',
            'sku' => 'CON-001',
            'type' => ProductType::Consumable,
        ]);

        $this->assertTrue($product->isConsumable());
    }

    public function test_product_has_fillable_fields(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Full Product',
            'sku' => 'FUL-001',
            'type' => ProductType::Part,
            'description' => 'A complete product description',
            'sale_price' => '99.99',
            'purchase_price' => '50.00',
            'tax_rate' => '20.00',
            'unit' => 'piece',
            'barcode' => '1234567890123',
            'is_active' => true,
        ]);

        $this->assertEquals('Full Product', $product->name);
        $this->assertEquals('FUL-001', $product->sku);
        $this->assertEquals('A complete product description', $product->description);
        $this->assertEquals('99.99', $product->sale_price);
        $this->assertEquals('50.00', $product->purchase_price);
        $this->assertEquals('20.00', $product->tax_rate);
        $this->assertEquals('piece', $product->unit);
        $this->assertEquals('1234567890123', $product->barcode);
        $this->assertTrue($product->is_active);
    }

    public function test_product_uses_soft_deletes(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Delete Me',
            'sku' => 'DEL-001',
            'type' => ProductType::Part,
        ]);

        $productId = $product->id;
        $product->delete();

        $this->assertNull(Product::find($productId));
        $this->assertNotNull(Product::withTrashed()->find($productId));
    }

    public function test_product_has_oem_numbers_as_array(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'OEM Product',
            'sku' => 'OEM-001',
            'type' => ProductType::Part,
            'oem_numbers' => ['OEM123', 'OEM456', 'OEM789'],
        ]);

        $this->assertIsArray($product->oem_numbers);
        $this->assertCount(3, $product->oem_numbers);
        $this->assertContains('OEM123', $product->oem_numbers);
    }

    public function test_product_has_cross_references_as_array(): void
    {
        $crossRefs = [
            ['brand' => 'Bosch', 'reference' => 'F026407022'],
            ['brand' => 'Mann', 'reference' => 'W712/80'],
        ];

        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Cross Ref Product',
            'sku' => 'CRF-001',
            'type' => ProductType::Part,
            'cross_references' => $crossRefs,
        ]);

        $this->assertIsArray($product->cross_references);
        $this->assertCount(2, $product->cross_references);
        $this->assertEquals('Bosch', $product->cross_references[0]['brand']);
    }

    public function test_product_default_active_state(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Default Active Product',
            'sku' => 'DEF-001',
            'type' => ProductType::Part,
        ]);

        $this->assertTrue($product->is_active);
    }

    public function test_product_scope_active(): void
    {
        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Active Product',
            'sku' => 'ACT-001',
            'type' => ProductType::Part,
            'is_active' => true,
        ]);

        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Inactive Product',
            'sku' => 'INA-001',
            'type' => ProductType::Part,
            'is_active' => false,
        ]);

        $activeProducts = Product::active()->get();

        $this->assertCount(1, $activeProducts);
        $this->assertEquals('Active Product', $activeProducts->first()->name);
    }

    public function test_product_scope_parts(): void
    {
        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Part',
            'sku' => 'PRT-001',
            'type' => ProductType::Part,
        ]);

        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Service',
            'sku' => 'SVC-001',
            'type' => ProductType::Service,
        ]);

        $parts = Product::parts()->get();

        $this->assertCount(1, $parts);
    }

    public function test_product_scope_services(): void
    {
        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Part',
            'sku' => 'PRT-001',
            'type' => ProductType::Part,
        ]);

        Product::create([
            'tenant_id' => $this->tenant->id,
            'company_id' => $this->company->id,
            'name' => 'Service',
            'sku' => 'SVC-001',
            'type' => ProductType::Service,
        ]);

        $services = Product::services()->get();

        $this->assertCount(1, $services);
    }
}
