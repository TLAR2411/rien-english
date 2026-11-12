<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

class LessonWordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'word' => ['required', 'string', 'max:100'],
            'meaning_en' => ['nullable', 'string', 'max:255'],
            'meaning_km' => ['nullable', 'string', 'max:255'],
            'example' => ['nullable', 'string', 'max:255'],
            'audio_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }
}
