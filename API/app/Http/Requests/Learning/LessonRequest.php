<?php

namespace App\Http\Requests\Learning;

use App\Enums\LessonSkill;
use App\Enums\PublishStatus;
use App\Rules\YouTubeUrl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Lesson editor form. The HTML is cleaned by the Lesson model when saved. */
class LessonRequest extends FormRequest
{
    public function rules(): array
    {
        $maxHtml = 'max:'.config('learning.lesson_max_length');

        // A listening lesson can be saved as a draft first; it needs its audio to be published
        $needsAudio = $this->input('skill') === LessonSkill::Listening->value
            && $this->input('status') === PublishStatus::Published->value;

        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'skill' => ['required', Rule::enum(LessonSkill::class)],
            'title_en' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('lessons')->ignore($this->route('lesson'))],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string', $maxHtml],
            'content_km' => ['nullable', 'string', $maxHtml],
            'transcript' => ['nullable', 'string', $maxHtml],
            // Writing lessons
            'checklist' => ['nullable', 'array', 'max:'.config('learning.writing.checklist_max')],
            'checklist.*' => ['required', 'string', 'max:200', 'distinct'],
            'min_words' => ['nullable', 'integer', 'min:1', 'max:'.config('learning.writing.max_words')],
            'max_words' => [
                'nullable', 'integer', 'max:'.config('learning.writing.max_words'),
                ...($this->filled('min_words') ? ['gte:min_words'] : []),
            ],
            'sample_answer' => ['nullable', 'string', $maxHtml],
            'audio_url' => [Rule::requiredIf($needsAudio), 'nullable', 'url:http,https', 'max:2048'],
            'video_url' => ['nullable', 'string', 'max:2048', new YouTubeUrl],
            'status' => ['required', Rule::enum(PublishStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'audio_url.required' => 'Add the audio before you publish a listening lesson.',
        ];
    }
}
