<?php

declare(strict_types=1);

namespace App\Modules\Document\Presentation\Requests;

use App\Modules\Identity\Domain\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateDocumentRequest extends FormRequest
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
        /** @var User|null $authenticatedUser */
        $authenticatedUser = $this->user();

        /** @var User $user */
        $user = $authenticatedUser;
        $tenantId = $user->tenant_id;

        return [
            'partner_id' => [
                'required',
                'uuid',
                Rule::exists('partners', 'id')->where('tenant_id', $tenantId),
            ],
            'vehicle_id' => [
                'nullable',
                'uuid',
                Rule::exists('vehicles', 'id')->where('tenant_id', $tenantId),
            ],
            'document_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:document_date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:document_date'],
            'currency' => ['nullable', 'string', 'size:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'source_document_id' => [
                'nullable',
                'uuid',
                Rule::exists('documents', 'id')->where('tenant_id', $tenantId),
            ],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => [
                'nullable',
                'uuid',
                Rule::exists('products', 'id')->where('tenant_id', $tenantId),
            ],
            'lines.*.description' => ['required', 'string', 'max:1000'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
