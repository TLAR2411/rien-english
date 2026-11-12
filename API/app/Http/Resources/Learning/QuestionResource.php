<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A question for the ADMIN quiz builder — includes the correct answers.
 * Never use this for students (see the student quiz resource).
 *
 * @mixin \App\Models\Learning\Question
 */
class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'type' => $this->type,
            'prompt' => $this->prompt,
            'explanation' => $this->explanation,
            'explanation_km' => $this->explanation_km,
            'points' => $this->points,
            'sort_order' => $this->sort_order,
            'options' => $this->whenLoaded('options', fn () => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->text,
                'is_correct' => $option->is_correct,
            ])),
        ];
    }
}
