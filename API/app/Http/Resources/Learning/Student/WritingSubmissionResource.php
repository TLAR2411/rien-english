<?php

namespace App\Http\Resources\Learning\Student;

use App\Support\WordCount;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A student's writing as the student sees it: their text, and the teacher's
 * score + feedback once it is reviewed.
 *
 * @mixin \App\Models\Learning\WritingSubmission
 */
class WritingSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson' => $this->whenLoaded('lesson', fn () => [
                ...$this->lesson->only(['id', 'slug', 'title_en', 'title_km']),
                'level' => $this->lesson->relationLoaded('course') ? $this->lesson->course->level?->code : null,
            ]),
            'text' => $this->text,
            'word_count' => WordCount::of($this->text),
            'status' => $this->status,
            'score' => $this->score,
            'feedback' => $this->feedback,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
