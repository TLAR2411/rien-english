<?php

namespace App\Http\Resources\Learning;

use App\Support\WordCount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A student's writing for the Writing Review screens. The list shows a short
 * `excerpt`; the review page (`withTask()`) also gets the full text and the lesson's task.
 *
 * @mixin \App\Models\Learning\WritingSubmission
 */
class WritingReviewResource extends JsonResource
{
    private bool $withTask = false;

    /** Full text + the lesson's task (text, checklist, word target, sample answer). */
    public function withTask(): static
    {
        $this->withTask = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $lesson = $this->lesson;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'word_count' => WordCount::of($this->text),
            'excerpt' => Str::limit(preg_replace('/\s+/u', ' ', $this->text), 120),
            'text' => $this->when($this->withTask, $this->text),
            'created_at' => $this->created_at,
            'reviewed_at' => $this->reviewed_at,
            'student' => $this->whenLoaded('user', fn () => $this->user->only(['id', 'name_en', 'name_kh', 'email'])),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->only(['id', 'name_en', 'name_kh'])),
            'lesson' => $this->whenLoaded('lesson', fn () => [
                ...$lesson->only(['id', 'slug', 'title_en', 'title_km', 'min_words', 'max_words']),
                'level' => $lesson->relationLoaded('course') ? $lesson->course->level?->code : null,
                ...($this->withTask ? $lesson->only(['content', 'checklist', 'sample_answer']) : []),
            ]),
        ];
    }
}
