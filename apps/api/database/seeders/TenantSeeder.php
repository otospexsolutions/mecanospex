<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Tenant\Domain\Enums\SubscriptionPlan;
use App\Modules\Tenant\Domain\Enums\TenantStatus;
use App\Modules\Tenant\Domain\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo tenant for development
        $tenant = Tenant::create([
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

        $tenant->domains()->create([
            'domain' => 'demo.autoerp.local',
            'is_primary' => true,
            'is_verified' => true,
        ]);

        $this->command->info("Created demo tenant: {$tenant->name} ({$tenant->slug})");

        // Create a trial tenant for testing
        $trialTenant = Tenant::create([
            'name' => 'Trial Business',
            'slug' => 'trial-business',
            'status' => TenantStatus::Active,
            'plan' => SubscriptionPlan::Trial,
            'country_code' => 'TN',
            'currency_code' => 'TND',
            'settings' => [
                'timezone' => 'Africa/Tunis',
                'locale' => 'fr',
                'date_format' => 'd/m/Y',
                'fiscal_year_start' => '01-01',
            ],
            'trial_ends_at' => now()->addDays(14),
            'subscription_ends_at' => null,
        ]);

        $trialTenant->domains()->create([
            'domain' => 'trial.autoerp.local',
            'is_primary' => true,
            'is_verified' => true,
        ]);

        $this->command->info("Created trial tenant: {$trialTenant->name} ({$trialTenant->slug})");
    }
}
