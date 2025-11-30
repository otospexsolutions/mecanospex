<?php

declare(strict_types=1);

namespace App\Modules\Product\Presentation\Requests;

use App\Modules\Product\Domain\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateProductRequest extends FormRequest
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
        $user = $this->user();
        $tenantId = $user?->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', new Enum(ProductType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'oem_numbers' => ['nullable', 'array'],
            'oem_numbers.*' => ['string', 'max:100'],
            'cross_references' => ['nullable', 'array'],
            'cross_references.*.brand' => ['required_with:cross_references', 'string', 'max:100'],
            'cross_references.*.reference' => ['required_with:cross_references', 'string', 'max:100'],
        ];
    }
}
