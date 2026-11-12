<?php

namespace App\Http\Resources\Learning\Student;

use App\Http\Resources\Learning\Site\LessonCardResource;
use Illuminate\Http\Request;

/**
 * A lesson the student finished: the card fields + when, and the best quiz score
 * (null until the student takes the quiz). Built by StudentProgress::doneLessons().
 *
 * @mixin \App\Models\Learning\Lesson
 */
class DoneLessonResource extends LessonCardResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'course' => $this->course?->only(['id', 'title', 'title_km']),
            'completed_at' => $this->completed_at,
            'best_percent' => $this->best_percent === null ? null : (int) $this->best_percent,
            'passed' => $this->passed,
        ];
    }
}
