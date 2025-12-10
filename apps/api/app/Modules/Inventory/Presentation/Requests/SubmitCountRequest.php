<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
