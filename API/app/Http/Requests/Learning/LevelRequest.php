<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->code))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:10', Rule::unique('levels')->ignore($this->route('level'))],
            'name' => ['required', 'string', 'max:100'],
            'name_km' => ['nullable', 'string', 'max:100'],
        ];
    }
}
