<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * "Set a new password" form, opened from the link in the reset email.
 * Same password rule as sign-up (Password::defaults() in AppServiceProvider).
 */
class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Str::lower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'This link is not valid. Please ask for a new one.',
            'email.required' => 'This link is not valid. Please ask for a new one.',
            'email.email' => 'This link is not valid. Please ask for a new one.',
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'The two passwords are not the same.',
            'password.min' => 'Use at least 8 characters, with letters and numbers.',
            'password.letters' => 'Use at least 8 characters, with letters and numbers.',
            'password.numbers' => 'Use at least 8 characters, with letters and numbers.',
        ];
    }
}
