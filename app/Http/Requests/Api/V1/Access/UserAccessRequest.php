<?php

namespace App\Http\Requests\Api\V1\Access;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UserAccessRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
            'permission_ids' => ['sometimes', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];
    }
}
