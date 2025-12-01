<?php

declare(strict_types=1);

namespace App\Modules\Company\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2'],
            'currency' => ['required', 'string', 'size:3'],
            'locale' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', 'max:50', 'timezone'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_street_2' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:100'],
            'address_state' => ['nullable', 'string', 'max:100'],
            'address_postal_code' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Company name is required.',
            'country_code.required' => 'Country code is required.',
            'country_code.size' => 'Country code must be a 2-letter ISO code.',
            'currency.required' => 'Currency is required.',
            'currency.size' => 'Currency must be a 3-letter ISO code.',
            'locale.required' => 'Locale is required.',
            'timezone.required' => 'Timezone is required.',
            'timezone.timezone' => 'Invalid timezone.',
        ];
    }
}
