<?php

namespace App\Http\Requests\Api\V1\Access;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:50', Rule::unique('permissions', 'name')->ignore($this->route('permission'))],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'boolean'],
        ];
    }
}
