<?php

namespace App\Http\Requests\Learning;

use App\Enums\LessonSkill;
use App\Enums\PublishStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Lesson editor form. The HTML is cleaned by the Lesson model when saved. */
class LessonRequest extends FormRequest
{
    public function rules(): array
    {
        $maxHtml = 'max:'.config('learning.lesson_max_length');

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'skill' => ['required', Rule::enum(LessonSkill::class)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('lessons')->ignore($this->route('lesson'))],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', $maxHtml],
            'content_km' => ['nullable', 'string', $maxHtml],
            'status' => ['required', Rule::enum(PublishStatus::class)],
        ];
    }
}
