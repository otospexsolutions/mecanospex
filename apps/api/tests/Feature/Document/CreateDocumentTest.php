<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Enums\PartnerType;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Product\Domain\Enums\ProductType;
use App\Modules\Product\Domain\Product;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreateDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Partner $customer;

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

        $this->customer = Partner::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'type' => PartnerType::Customer,
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Oil Change Service',
            'sku' => 'SVC-001',
            'type' => ProductType::Service,
            'sale_price' => '50.00',
            'tax_rate' => '20.00',
        ]);
    }

    public function test_partner_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['partner_id']);
    }

    public function test_document_date_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['document_date']);
    }

    public function test_lines_are_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lines']);
    }

    public function test_line_description_is_required(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.description']);
    }

    public function test_line_quantity_must_be_positive(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '-1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['lines.0.quantity']);
    }

    public function test_can_create_quote(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'currency' => 'EUR',
                'notes' => 'Test quote',
                'lines' => [
                    [
                        'description' => 'Oil Change',
                        'quantity' => '1.00',
                        'unit_price' => '50.00',
                        'tax_rate' => '20.00',
                    ],
                    [
                        'description' => 'Oil Filter',
                        'quantity' => '1.00',
                        'unit_price' => '25.00',
                        'tax_rate' => '20.00',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'quote')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.partner_id', $this->customer->id)
            ->assertJsonPath('data.subtotal', '75.00')
            ->assertJsonPath('data.tax_amount', '15.00')
            ->assertJsonPath('data.total', '90.00')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'document_number',
                    'type',
                    'status',
                    'partner_id',
                    'document_date',
                    'subtotal',
                    'tax_amount',
                    'total',
                    'currency',
                    'lines' => [
                        '*' => ['id', 'line_number', 'description', 'quantity', 'unit_price', 'tax_rate', 'line_total'],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('documents', [
            'tenant_id' => $this->tenant->id,
            'partner_id' => $this->customer->id,
            'type' => 'quote',
            'status' => 'draft',
        ]);

        $this->assertDatabaseCount('document_lines', 2);
    }

    public function test_can_create_invoice(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/invoices', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'currency' => 'EUR',
                'lines' => [
                    [
                        'description' => 'Consulting Services',
                        'quantity' => '2.00',
                        'unit_price' => '100.00',
                        'tax_rate' => '20.00',
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'invoice')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', '200.00')
            ->assertJsonPath('data.tax_amount', '40.00')
            ->assertJsonPath('data.total', '240.00');
    }

    public function test_document_number_is_auto_generated(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertCreated();
        $documentNumber = $response->json('data.document_number');

        $this->assertNotNull($documentNumber);
        $this->assertStringStartsWith('QT-', $documentNumber);
    }

    public function test_partner_must_belong_to_same_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        $otherPartner = Partner::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Customer',
            'type' => PartnerType::Customer,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $otherPartner->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['partner_id']);
    }

    public function test_unauthenticated_user_cannot_create_document(): void
    {
        $response = $this->postJson('/api/v1/quotes', [
            'partner_id' => $this->customer->id,
            'document_date' => now()->toDateString(),
            'lines' => [
                [
                    'description' => 'Service',
                    'quantity' => '1.00',
                    'unit_price' => '100.00',
                ],
            ],
        ]);

        $response->assertUnauthorized();
    }

    public function test_user_without_permission_cannot_create_quote(): void
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
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'description' => 'Service',
                        'quantity' => '1.00',
                        'unit_price' => '100.00',
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_can_create_document_with_product_reference(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/quotes', [
                'partner_id' => $this->customer->id,
                'document_date' => now()->toDateString(),
                'lines' => [
                    [
                        'product_id' => $this->product->id,
                        'description' => $this->product->name,
                        'quantity' => '1.00',
                        'unit_price' => $this->product->sale_price,
                        'tax_rate' => $this->product->tax_rate,
                    ],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.lines.0.product_id', $this->product->id);
    }
}
