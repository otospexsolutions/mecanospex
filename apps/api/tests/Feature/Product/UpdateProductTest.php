<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Product\Domain\Enums\ProductType;
use App\Modules\Product\Domain\Product;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Product $product;

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

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Original Product',
            'sku' => 'ORG-001',
            'type' => ProductType::Part,
            'sale_price' => '50.00',
        ]);
    }

    public function test_can_update_product_name(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Updated Product Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Product Name');

        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => 'Updated Product Name',
        ]);
    }

    public function test_can_update_product_price(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'sale_price' => '75.99',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.sale_price', '75.99');
    }

    public function test_can_update_product_type(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'type' => 'service',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'service');
    }

    public function test_sku_uniqueness_on_update(): void
    {
        Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Product',
            'sku' => 'OTH-001',
            'type' => ProductType::Part,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'sku' => 'OTH-001',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_can_update_own_sku_to_same_value(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'sku' => 'ORG-001',
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
    }

    public function test_can_update_oem_numbers(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'oem_numbers' => ['NEW-OEM-123', 'NEW-OEM-456'],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.oem_numbers', ['NEW-OEM-123', 'NEW-OEM-456']);
    }

    public function test_can_deactivate_product(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'is_active' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_returns_404_for_nonexistent_product(): void
    {
        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$fakeId}", [
                'name' => 'Updated Name',
            ]);

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_update_product(): void
    {
        $response = $this->patchJson("/api/v1/products/{$this->product->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_update_product(): void
    {
        $viewerUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);
        $viewerUser->assignRole('viewer');

        $response = $this->actingAs($viewerUser, 'sanctum')
            ->patchJson("/api/v1/products/{$this->product->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_cannot_update_product_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $otherProduct = Product::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Product',
            'sku' => 'OTH-001',
            'type' => ProductType::Part,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/products/{$otherProduct->id}", [
                'name' => 'Hacked Name',
            ]);

        $response->assertNotFound();
    }
}
