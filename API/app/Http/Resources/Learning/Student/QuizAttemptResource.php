<?php

namespace App\Http\Resources\Learning\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row in "My Quizzes": which quiz, the score and when. Open it for the full result.
 *
 * @mixin \App\Models\Learning\QuizAttempt
 */
class QuizAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_title' => $this->quiz?->title,
            'lesson' => $this->quiz?->lesson?->only(['id', 'slug', 'title_en', 'title_km']),
            'score' => $this->score,
            'total' => $this->total,
            'percent' => $this->percent,
            'passed' => $this->passed,
            'finished_at' => $this->finished_at,
        ];
    }
}
