<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * "Forgot password?" form: only the email. Messages are also translation
 * keys on the website (WEB/src/plugins/i18n/locales).
 */
class ForgotPasswordRequest extends FormRequest
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
            'email' => ['required', 'string', 'email:rfc', 'max:191'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please enter a valid email.',
        ];
    }
}
