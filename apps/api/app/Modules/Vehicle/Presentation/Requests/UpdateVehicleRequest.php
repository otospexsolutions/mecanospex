<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Presentation\Requests;

use App\Modules\Identity\Domain\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->user();
        $tenantId = $user->tenant_id;
        $vehicleId = $this->route('vehicle');

        return [
            'partner_id' => [
                'nullable',
                'uuid',
                Rule::exists('partners', 'id')->where('tenant_id', $tenantId),
            ],
            'license_plate' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('vehicles', 'license_plate')
                    ->where('tenant_id', $tenantId)
                    ->ignore($vehicleId),
            ],
            'brand' => ['sometimes', 'string', 'max:100'],
            'model' => ['sometimes', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'vin' => [
                'nullable',
                'string',
                'size:17',
                'regex:/^[A-HJ-NPR-Z0-9]{17}$/i',
                Rule::unique('vehicles', 'vin')
                    ->where('tenant_id', $tenantId)
                    ->ignore($vehicleId),
            ],
            'engine_code' => ['nullable', 'string', 'max:50'],
            'fuel_type' => ['nullable', 'string', 'max:30'],
            'transmission' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'vin.size' => 'The VIN must be exactly 17 characters.',
            'vin.regex' => 'The VIN contains invalid characters. VINs cannot contain I, O, or Q.',
        ];
    }
}
