<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Presentation\Requests;

use App\Modules\Accounting\Domain\Enums\AccountType;
use App\Modules\Identity\Domain\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CreateAccountRequest extends FormRequest
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
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('accounts', 'code')->where('tenant_id', $tenantId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', new Enum(AccountType::class)],
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
