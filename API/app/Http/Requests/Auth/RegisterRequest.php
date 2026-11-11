<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Student sign-up form. The messages are short English sentences that the
 * website also uses as translation keys (WEB/src/plugins/i18n/locales).
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name_en' => trim((string) $this->name_en),
            'name_kh' => trim((string) $this->name_kh) ?: null,
            'email' => Str::lower(trim((string) $this->email)),
        ]);
    }

    public function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:100'],
            'name_kh' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            // Hidden field on the form: people never see it, spam bots fill it in
            'website' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'name_en.required' => 'Please enter your name.',
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please enter a valid email.',
            'email.unique' => 'This email already has an account. Please log in.',
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'The two passwords are not the same.',
            'password.min' => 'Use at least 8 characters, with letters and numbers.',
            'password.letters' => 'Use at least 8 characters, with letters and numbers.',
            'password.numbers' => 'Use at least 8 characters, with letters and numbers.',
            'website.prohibited' => 'Sign-up failed. Please try again.',
        ];
    }
}
