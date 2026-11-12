<?php

namespace App\Http\Requests\Learning\Student;

use App\Enums\WritingStatus;
use App\Support\WordCount;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A student sends their writing for a writing lesson.
 * - The lesson must be a published writing lesson (else 404).
 * - The text must fit the lesson's word target (min_words – max_words).
 * - Only one writing per lesson can wait for review at a time.
 */
class SubmitWritingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_unless($this->route('lesson')?->acceptsWriting(), 404);
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:'.config('learning.writing.max_length'), $this->wordTarget(...)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $waiting = $this->user()->writingSubmissions()
                ->where('lesson_id', $this->route('lesson')->id)
                ->where('status', WritingStatus::Pending)
                ->exists();

            if ($waiting) {
                $validator->errors()->add('text', 'Your writing is waiting for review. You can write again after the teacher checks it.');
            }
        });
    }

    private function wordTarget(string $attribute, mixed $value, Closure $fail): void
    {
        $lesson = $this->route('lesson');
        $count = WordCount::of($value);

        if ($lesson->min_words && $count < $lesson->min_words) {
            $fail("Write at least {$lesson->min_words} words (now {$count}).");
        } elseif ($lesson->max_words && $count > $lesson->max_words) {
            $fail("Write at most {$lesson->max_words} words (now {$count}).");
        }
    }
}
