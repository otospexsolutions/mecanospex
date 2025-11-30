<?php

declare(strict_types=1);

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Domain\Account;
use App\Modules\Accounting\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ListAccountsTest extends TestCase
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
            'password' => bcrypt('password'),
            'status' => UserStatus::Active,
        ]);
        $this->user->givePermissionTo(['accounts.view', 'accounts.manage']);
    }

    public function test_user_can_list_accounts(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '2000',
            'name' => 'Liabilities',
            'type' => AccountType::Liability,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_accounts_are_filtered_by_tenant(): void
    {
        // Account for current tenant
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        // Account for another tenant
        $otherTenant = Tenant::create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);

        Account::create([
            'tenant_id' => $otherTenant->id,
            'code' => '1000',
            'name' => 'Other Assets',
            'type' => AccountType::Asset,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Assets', $response->json('data.0.name'));
    }

    public function test_accounts_can_be_filtered_by_type(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '2000',
            'name' => 'Liabilities',
            'type' => AccountType::Liability,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts?type=asset');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('asset', $response->json('data.0.type'));
    }

    public function test_accounts_can_be_searched(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1100',
            'name' => 'Cash and Cash Equivalents',
            'type' => AccountType::Asset,
        ]);

        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1200',
            'name' => 'Accounts Receivable',
            'type' => AccountType::Asset,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts?search=Cash');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertStringContainsString('Cash', $response->json('data.0.name'));
    }

    public function test_accounts_ordered_by_code(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '2000',
            'name' => 'Liabilities',
            'type' => AccountType::Liability,
        ]);

        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $this->assertEquals('1000', $response->json('data.0.code'));
        $this->assertEquals('2000', $response->json('data.1.code'));
    }

    public function test_accounts_can_filter_active_only(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Active Account',
            'type' => AccountType::Asset,
            'is_active' => true,
        ]);

        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1001',
            'name' => 'Inactive Account',
            'type' => AccountType::Asset,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts?active=1');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Active Account', $response->json('data.0.name'));
    }

    public function test_unauthorized_user_cannot_list_accounts(): void
    {
        $this->user->revokePermissionTo('accounts.view');

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertStatus(403);
    }

    public function test_can_view_single_account(): void
    {
        $account = Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/v1/accounts/{$account->id}");

        $response->assertStatus(200);
        $this->assertEquals('1000', $response->json('data.code'));
        $this->assertEquals('Assets', $response->json('data.name'));
    }

    public function test_accounts_include_balance_field(): void
    {
        Account::create([
            'tenant_id' => $this->tenant->id,
            'code' => '1000',
            'name' => 'Assets',
            'type' => AccountType::Asset,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/accounts');

        $response->assertStatus(200);
        $this->assertArrayHasKey('balance', $response->json('data.0'));
    }
}
