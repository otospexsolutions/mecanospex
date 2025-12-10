<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Company\Domain\Company;
use App\Modules\Tenant\Domain\Tenant;
use App\Modules\Treasury\Domain\PaymentMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first tenant and company (demo tenant)
        $tenant = Tenant::first();
        $company = Company::first();

        if (!$tenant || !$company) {
            $this->command->error('No tenant or company found. Please run DatabaseSeeder first.');
            return;
        }

        $methods = [
            // Cash
            [
                'code' => 'CASH',
                'name' => 'Cash',
                'is_physical' => true,
                'has_maturity' => false,
                'requires_third_party' => false,
                'is_push' => false,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 1,
                'is_active' => true,
            ],
            // Check
            [
                'code' => 'CHECK',
                'name' => 'Check',
                'is_physical' => true,
                'has_maturity' => true,
                'requires_third_party' => false,
                'is_push' => false,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 2,
                'is_active' => true,
            ],
            // Bank Transfer
            [
                'code' => 'TRANSFER',
                'name' => 'Bank Transfer',
                'is_physical' => false,
                'has_maturity' => false,
                'requires_third_party' => false,
                'is_push' => true,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 3,
                'is_active' => true,
            ],
            // Credit Card
            [
                'code' => 'CARD',
                'name' => 'Credit/Debit Card',
                'is_physical' => false,
                'has_maturity' => false,
                'requires_third_party' => true,
                'is_push' => false,
                'has_deducted_fees' => true,
                'is_restricted' => false,
                'position' => 4,
                'is_active' => true,
            ],
            // Direct Debit
            [
                'code' => 'DIRECT_DEBIT',
                'name' => 'Direct Debit',
                'is_physical' => false,
                'has_maturity' => true,
                'requires_third_party' => false,
                'is_push' => true,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 5,
                'is_active' => true,
            ],
            // PayPal
            [
                'code' => 'PAYPAL',
                'name' => 'PayPal',
                'is_physical' => false,
                'has_maturity' => false,
                'requires_third_party' => true,
                'is_push' => false,
                'has_deducted_fees' => true,
                'is_restricted' => false,
                'position' => 6,
                'is_active' => true,
            ],
            // Promissory Note (LCR - Letter de Change Relevé)
            [
                'code' => 'LCR',
                'name' => 'Promissory Note (LCR)',
                'is_physical' => true,
                'has_maturity' => true,
                'requires_third_party' => false,
                'is_push' => false,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 7,
                'is_active' => true,
            ],
            // Bill of Exchange
            [
                'code' => 'BILL_EXCHANGE',
                'name' => 'Bill of Exchange',
                'is_physical' => true,
                'has_maturity' => true,
                'requires_third_party' => false,
                'is_push' => false,
                'has_deducted_fees' => false,
                'is_restricted' => false,
                'position' => 8,
                'is_active' => true,
            ],
            // Meal Voucher (Ticket Restaurant)
            [
                'code' => 'MEAL_VOUCHER',
                'name' => 'Meal Voucher',
                'is_physical' => true,
                'has_maturity' => false,
                'requires_third_party' => true,
                'is_push' => false,
                'has_deducted_fees' => true,
                'is_restricted' => true,
                'position' => 9,
                'is_active' => true,
            ],
            // Cryptocurrency
            [
                'code' => 'CRYPTO',
                'name' => 'Cryptocurrency',
                'is_physical' => false,
                'has_maturity' => false,
                'requires_third_party' => true,
                'is_push' => true,
                'has_deducted_fees' => true,
                'is_restricted' => false,
                'position' => 10,
                'is_active' => false, // Disabled by default
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create(array_merge([
                'id' => Str::uuid()->toString(),
                'tenant_id' => $tenant->id,
                'company_id' => $company->id,
            ], $method));
        }

        $this->command->info('Created ' . count($methods) . ' standard payment methods for company: ' . $company->name);
    }
}
