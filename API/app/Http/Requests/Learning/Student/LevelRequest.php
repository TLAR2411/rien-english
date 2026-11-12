<?php

namespace App\Http\Requests\Learning\Student;

use Illuminate\Foundation\Http\FormRequest;

/** A student chooses their level in the top bar. */
class LevelRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
        ];
    }
}
