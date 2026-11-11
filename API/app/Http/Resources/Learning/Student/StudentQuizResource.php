<?php

namespace App\Http\Resources\Learning\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A quiz as students see it BEFORE they submit: no correct answers, no explanations.
 * (Quizzes are graded in Laravel only — PLAN.md golden rule 1.)
 *
 * @mixin \App\Models\Learning\Quiz
 */
class StudentQuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'pass_score' => $this->pass_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => StudentQuestionResource::collection($this->whenLoaded('questions')),
        ];
    }
}
