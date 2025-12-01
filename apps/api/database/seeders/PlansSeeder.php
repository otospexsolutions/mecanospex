<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'id' => Str::uuid()->toString(),
                'code' => 'starter',
                'name' => 'Starter',
                'description' => 'Perfect for small businesses just getting started',
                'limits' => [
                    'max_companies' => 1,
                    'max_locations' => 1,
                    'max_users' => 3,
                ],
                'price_monthly' => 29.00,
                'currency' => 'TND',
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'id' => Str::uuid()->toString(),
                'code' => 'professional',
                'name' => 'Professional',
                'description' => 'For growing businesses with multiple locations',
                'limits' => [
                    'max_companies' => 3,
                    'max_locations' => 5,
                    'max_users' => 10,
                ],
                'price_monthly' => 79.00,
                'currency' => 'TND',
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'id' => Str::uuid()->toString(),
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Unlimited access for large organizations',
                'limits' => [
                    'max_companies' => 999,
                    'max_locations' => 999,
                    'max_users' => 999,
                ],
                'price_monthly' => null, // Custom pricing
                'currency' => 'TND',
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }
    }
}
