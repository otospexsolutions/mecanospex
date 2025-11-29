<?php

declare(strict_types=1);

namespace Tests\Feature\Identity;

use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\User;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'tenantId',
                        'name',
                        'email',
                        'status',
                        'roles',
                        'permissions',
                    ],
                    'token',
                    'tokenType',
                ],
                'meta' => [
                    'timestamp',
                    'request_id',
                ],
            ]);

        $this->assertEquals($user->id, $response->json('data.user.id'));
        $this->assertEquals('Bearer', $response->json('data.tokenType'));
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'status' => UserStatus::Inactive,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'test@example.com');
    }

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    public function test_user_can_logout(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        // First login to get a token
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.token');

        // Then logout
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJsonPath('data.message', 'Successfully logged out');

        // Verify token was deleted
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
        ]);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_with_device_info_creates_device_record(): void
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'status' => UserStatus::Active,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'iPhone 15 Pro',
            'device_id' => 'unique-device-id-123',
            'platform' => 'ios',
            'platform_version' => '17.0',
            'app_version' => '1.0.0',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'name' => 'iPhone 15 Pro',
            'device_id' => 'unique-device-id-123',
            'platform' => 'ios',
            'platform_version' => '17.0',
            'app_version' => '1.0.0',
            'type' => 'mobile',
        ]);
    }
}
