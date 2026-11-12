<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class QuizRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'pass_score' => ['required', 'integer', 'between:1,100'],
            'time_limit_minutes' => ['nullable', 'integer', 'between:1,180'],
        ];
    }
}
