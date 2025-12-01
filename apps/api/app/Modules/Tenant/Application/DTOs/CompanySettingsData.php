<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Application\DTOs;

use App\Modules\Tenant\Domain\Tenant;

class CompanySettingsData
{
    /**
     * @param  array<string, string|null>|null  $address
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $legalName,
        public readonly ?string $taxId,
        public readonly ?string $registrationNumber,
        public readonly ?array $address,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $website,
        public readonly ?string $logoUrl,
        public readonly ?string $primaryColor,
        public readonly ?string $countryCode,
        public readonly ?string $currencyCode,
        public readonly ?string $timezone,
        public readonly ?string $dateFormat,
        public readonly ?string $locale,
    ) {}

    /**
     * Create from a Tenant model.
     *
     * @return array<string, mixed>
     */
    public static function fromTenant(Tenant $tenant): array
    {
        $address = $tenant->address ?? [];

        return [
            'name' => $tenant->name,
            'legalName' => $tenant->legal_name,
            'taxId' => $tenant->tax_id,
            'registrationNumber' => $tenant->registration_number,
            'address' => [
                'street' => $address['street'] ?? null,
                'city' => $address['city'] ?? null,
                'postalCode' => $address['postal_code'] ?? null,
                'country' => $address['country'] ?? null,
            ],
            'phone' => $tenant->phone,
            'email' => $tenant->email,
            'website' => $tenant->website,
            'logoUrl' => $tenant->logo_path ? asset('storage/'.$tenant->logo_path) : null,
            'primaryColor' => $tenant->primary_color,
            'countryCode' => $tenant->country_code,
            'currencyCode' => $tenant->currency_code,
            'timezone' => $tenant->timezone,
            'dateFormat' => $tenant->date_format,
            'locale' => $tenant->locale,
        ];
    }
}
