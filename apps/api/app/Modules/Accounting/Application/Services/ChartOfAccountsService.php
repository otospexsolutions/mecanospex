<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Application\Services;

use App\Modules\Accounting\Domain\Account;
use App\Modules\Accounting\Domain\Enums\SystemAccountPurpose;
use App\Modules\Company\Domain\Company;
use Database\Seeders\TunisiaChartOfAccountsSeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Service for managing chart of accounts operations.
 *
 * Handles:
 * - Country-specific COA seeding
 * - System purpose validation
 * - Account purpose assignment
 */
class ChartOfAccountsService
{
    /**
     * Seed chart of accounts for a newly created company.
     * Automatically selects the appropriate seeder based on country.
     *
     * @throws RuntimeException When no seeder exists for the country
     */
    public function seedForCompany(Company $company): void
    {
        $seederClass = $this->getSeederForCountry($company->country_code);

        if ($seederClass === null) {
            throw new RuntimeException(
                "No chart of accounts seeder available for country: {$company->country_code}. ".
                'Please create a seeder for this country first.'
            );
        }

        DB::transaction(function () use ($company, $seederClass): void {
            /** @var TunisiaChartOfAccountsSeeder $seeder */
            $seeder = new $seederClass;
            $seeder->run($company->id, $company->tenant_id);
        });
    }

    /**
     * Check if a company has all required system accounts.
     *
     * @return array{valid: bool, missing_purposes: list<string>}
     */
    public function validateCompanyAccounts(string $companyId): array
    {
        $missing = [];

        foreach (SystemAccountPurpose::requiredPurposes() as $purpose) {
            $account = Account::findByPurpose($companyId, $purpose);

            if ($account === null) {
                $missing[] = $purpose->value;
            }
        }

        return [
            'valid' => empty($missing),
            'missing_purposes' => $missing,
        ];
    }

    /**
     * Get account by system purpose for a company.
     *
     * @throws RuntimeException When account not found
     */
    public function getAccountByPurpose(string $companyId, SystemAccountPurpose $purpose): Account
    {
        return Account::findByPurposeOrFail($companyId, $purpose);
    }

    /**
     * List all accounts with their system purposes for admin display.
     *
     * @return list<array{id: string, code: string, name: string, type: string, system_purpose: string|null, is_system: bool}>
     */
    public function getAccountsWithPurposes(string $companyId): array
    {
        /** @var list<array{id: string, code: string, name: string, type: string, system_purpose: string|null, is_system: bool}> */
        return Account::forCompany($companyId)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'system_purpose', 'is_system'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'system_purpose' => $account->system_purpose?->value,
                'is_system' => $account->is_system,
            ])
            ->toArray();
    }

    /**
     * Assign a system purpose to an account (admin function).
     *
     * @throws RuntimeException If purpose already assigned to another account
     */
    public function assignPurpose(string $companyId, string $accountId, SystemAccountPurpose $purpose): void
    {
        // Check if another account already has this purpose
        $existing = Account::forCompany($companyId)
            ->withPurpose($purpose)
            ->where('id', '!=', $accountId)
            ->first();

        if ($existing !== null) {
            throw new RuntimeException(
                "Purpose '{$purpose->value}' is already assigned to account {$existing->code} ({$existing->name}). ".
                'Remove it from that account first.'
            );
        }

        $account = Account::where('company_id', $companyId)
            ->where('id', $accountId)
            ->firstOrFail();

        $account->update(['system_purpose' => $purpose]);
    }

    /**
     * Remove system purpose from an account.
     */
    public function removePurpose(string $companyId, string $accountId): void
    {
        Account::where('company_id', $companyId)
            ->where('id', $accountId)
            ->update(['system_purpose' => null]);
    }

    /**
     * Get the seeder class for a given country.
     *
     * @return class-string|null
     */
    private function getSeederForCountry(string $countryCode): ?string
    {
        $seeders = [
            'TN' => TunisiaChartOfAccountsSeeder::class,
            // Future seeders:
            // 'FR' => FranceChartOfAccountsSeeder::class,
            // 'IT' => ItalyChartOfAccountsSeeder::class,
        ];

        return $seeders[$countryCode] ?? null;
    }

    /**
     * Get list of supported countries for COA seeding.
     *
     * @return list<string>
     */
    public function getSupportedCountries(): array
    {
        return ['TN'];
    }

    /**
     * Get all available system purposes with labels.
     *
     * @return list<array{value: string, label: string}>
     */
    public function getAvailablePurposes(): array
    {
        return array_map(
            fn (SystemAccountPurpose $purpose): array => [
                'value' => $purpose->value,
                'label' => $purpose->label(),
            ],
            SystemAccountPurpose::cases()
        );
    }
}
