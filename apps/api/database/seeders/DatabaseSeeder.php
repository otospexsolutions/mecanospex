<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Company\Domain\Company;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Enums\MembershipRole;
use App\Modules\Company\Domain\Enums\MembershipStatus;
use App\Modules\Company\Domain\UserCompanyMembership;
use App\Modules\Identity\Domain\User;
use App\Modules\Partner\Domain\Partner;
use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with test data.
     */
    public function run(): void
    {
        $this->command->info('Creating roles and permissions...');
        $this->call(RolesAndPermissionsSeeder::class);

        $this->command->info('Creating demo tenant...');
        $tenant = $this->createTenant();

        $this->command->info('Creating demo company...');
        $company = $this->createCompany($tenant);

        $this->command->info('Creating test users...');
        $this->createUsers($tenant, $company);

        $this->command->info('Creating test partners...');
        $this->createPartners($tenant, $company);

        $this->command->info('Database seeding completed!');
    }

    private function createTenant(): Tenant
    {
        return Tenant::create([
            'name' => 'Demo Garage',
            'slug' => 'demo-garage',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Professional,
            'tax_id' => 'FR12345678901',
            'country_code' => 'FR',
            'currency_code' => 'EUR',
            'settings' => [
                'timezone' => 'Europe/Paris',
                'locale' => 'fr',
                'date_format' => 'd/m/Y',
                'fiscal_year_start' => '01-01',
            ],
            'trial_ends_at' => null,
            'subscription_ends_at' => now()->addYear(),
        ]);
    }

    private function createCompany(Tenant $tenant): Company
    {
        return Company::create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Garage',
            'legal_name' => 'Demo Garage SARL',
            'country_code' => 'FR',
            'tax_id' => 'FR12345678901',
            'currency' => 'EUR',
            'locale' => 'fr',
            'timezone' => 'Europe/Paris',
            'date_format' => 'd/m/Y',
            'fiscal_year_start_month' => 1,
            'status' => CompanyStatus::Active,
            'is_headquarters' => true,
        ]);
    }

    private function createUsers(Tenant $tenant, Company $company): void
    {
        // Set the team (tenant) context for Spatie permissions
        setPermissionsTeamId($tenant->id);

        // Test User - assign manager role (full operational access)
        $testUser = User::create([
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
            'preferences' => [],
        ]);

        // Create company membership for test user (manager role)
        UserCompanyMembership::create([
            'user_id' => $testUser->id,
            'company_id' => $company->id,
            'role' => MembershipRole::Manager,
            'is_primary' => true,
            'status' => MembershipStatus::Active,
            'accepted_at' => now(),
        ]);

        // Assign manager role with tenant scope
        $managerRole = Role::where('name', 'manager')->where('guard_name', 'sanctum')->first();
        if ($managerRole) {
            $testUser->assignRole($managerRole);
            $this->command->info('Assigned manager role to test@example.com');
        }

        // Admin User - assign admin role (full access)
        $adminUser = User::create([
            'id' => Str::uuid()->toString(),
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'status' => 'active',
            'email_verified_at' => now(),
            'preferences' => [],
        ]);

        // Create company membership for admin user (owner role)
        UserCompanyMembership::create([
            'user_id' => $adminUser->id,
            'company_id' => $company->id,
            'role' => MembershipRole::Owner,
            'is_primary' => true,
            'status' => MembershipStatus::Active,
            'accepted_at' => now(),
        ]);

        // Assign admin role with tenant scope
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        if ($adminRole) {
            $adminUser->assignRole($adminRole);
            $this->command->info('Assigned admin role to admin@example.com');
        }
    }

    private function createPartners(Tenant $tenant, Company $company): void
    {
        $partners = [
            [
                'name' => 'Acme Corporation',
                'type' => 'customer',
                'code' => 'ACME',
                'email' => 'contact@acme.com',
                'phone' => '+33123456789',
            ],
            [
                'name' => 'TechSupply Inc',
                'type' => 'supplier',
                'code' => 'TECH',
                'email' => 'orders@techsupply.com',
                'phone' => '+33987654321',
            ],
            [
                'name' => 'Auto Parts France',
                'type' => 'customer',
                'code' => 'APF',
                'email' => 'info@autoparts.fr',
                'phone' => '+33111222333',
            ],
            [
                'name' => 'Client Premium SA',
                'type' => 'customer',
                'code' => 'PREM',
                'email' => 'premium@client.com',
                'phone' => '+33444555666',
            ],
        ];

        foreach ($partners as $partner) {
            Partner::create([
                'id' => Str::uuid()->toString(),
                'company_id' => $company->id,
                'type' => $partner['type'],
                'code' => $partner['code'],
                'name' => $partner['name'],
                'email' => $partner['email'],
                'phone' => $partner['phone'],
                'country_code' => 'FR',
            ]);
        }
    }
}
