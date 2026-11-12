<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A quiz for ADMIN screens (questions include the correct answers).
 *
 * @mixin \App\Models\Learning\Quiz
 */
class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'title' => $this->title,
            'pass_score' => $this->pass_score,
            'time_limit_minutes' => $this->time_limit_minutes,
            'questions_count' => $this->whenCounted('questions'),
            'questions' => QuestionResource::collection($this->whenLoaded('questions')),
            'updated_at' => $this->updated_at,
        ];
    }
}
