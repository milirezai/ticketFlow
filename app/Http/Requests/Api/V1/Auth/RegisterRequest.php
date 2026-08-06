<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'nullable|min:3|max:30',
            'email' => 'required|email|unique:users,email',
            'last_name' => 'nullable|min:5|max:30',
            'password' => 'required|unique:users,password|min:8|max:12|confirmed',
            'mobile' => 'nullable|min:11|max:11|unique:users,mobile',
            'profile_photo_path' => 'nullable|image|extensions:png,jpg,jpeg'
        ];
    }
}
