<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CountryTaxRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CountryTaxRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxRates = [
            // Tunisia tax rates
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'TN',
                'name' => 'TVA 19%',
                'rate' => 19.00,
                'code' => 'TVA_19',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'TN',
                'name' => 'TVA 13%',
                'rate' => 13.00,
                'code' => 'TVA_13',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'TN',
                'name' => 'TVA 7%',
                'rate' => 7.00,
                'code' => 'TVA_7',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'TN',
                'name' => 'Exonéré',
                'rate' => 0.00,
                'code' => 'EXONERE',
                'is_default' => false,
                'is_active' => true,
            ],
            // France tax rates
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'FR',
                'name' => 'TVA 20%',
                'rate' => 20.00,
                'code' => 'TVA_20',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'FR',
                'name' => 'TVA 10%',
                'rate' => 10.00,
                'code' => 'TVA_10',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'FR',
                'name' => 'TVA 5.5%',
                'rate' => 5.50,
                'code' => 'TVA_5_5',
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'id' => Str::uuid()->toString(),
                'country_code' => 'FR',
                'name' => 'TVA 2.1%',
                'rate' => 2.10,
                'code' => 'TVA_2_1',
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($taxRates as $taxRate) {
            CountryTaxRate::updateOrCreate(
                ['id' => $taxRate['id']],
                $taxRate
            );
        }
    }
}
