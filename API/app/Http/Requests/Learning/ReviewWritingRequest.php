<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

/** A teacher's review of a student's writing: score 0–100 and feedback. */
class ReviewWritingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['required', 'string', 'max:'.config('learning.writing.feedback_max_length')],
        ];
    }
}
