<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserPasswordRequest extends FormRequest
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
            'old_password' => 'required|min:8',
            'new_password' => [
                'required',
                'min:8',
                'different:old_password', // Ensures the new password is not the same as the old one.
                'confirmed', // Requires a matching 'new_password_confirmation' field.
            ],
        ];
    }
}
