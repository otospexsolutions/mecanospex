<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Presentation\Requests;

use App\Modules\Identity\Domain\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'uuid',
                Rule::exists('accounts', 'id')->where('tenant_id', $tenantId),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
