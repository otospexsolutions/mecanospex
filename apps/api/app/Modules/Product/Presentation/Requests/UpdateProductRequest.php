<?php

declare(strict_types=1);

namespace App\Modules\Product\Presentation\Requests;

use App\Modules\Product\Domain\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore($productId),
            ],
            'type' => ['sometimes', new Enum(ProductType::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'sale_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'oem_numbers' => ['sometimes', 'nullable', 'array'],
            'oem_numbers.*' => ['string', 'max:100'],
            'cross_references' => ['sometimes', 'nullable', 'array'],
            'cross_references.*.brand' => ['required_with:cross_references', 'string', 'max:100'],
            'cross_references.*.reference' => ['required_with:cross_references', 'string', 'max:100'],
        ];
    }
}
