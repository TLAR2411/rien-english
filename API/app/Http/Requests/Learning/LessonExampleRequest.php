<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class LessonExampleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sentence' => ['required', 'string', 'max:255'],
            'translation_km' => ['nullable', 'string', 'max:255'],
            'audio_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
