<?php

namespace App\Http\Resources\Learning\Student;

use App\Enums\QuestionType;
use App\Models\Learning\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A question as students see it. Never contains is_correct or the explanation.
 * - choice types: the options to pick from (id + text)
 * - fill blank:   nothing extra (the student types)
 * - word order:   the words of the answer, mixed up
 *
 * @mixin Question
 */
class StudentQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'prompt' => $this->prompt,
            'points' => $this->points,
            'options' => $this->when($this->type->isChoice(), fn () => $this->options->map->only(['id', 'text'])->values()),
            'words' => $this->when($this->type === QuestionType::WordOrder, fn () => self::mixedWords($this->resource)),
        ];
    }

    /** The first accepted answer's words, mixed up (the same order every time for this question). */
    public static function mixedWords(Question $question): array
    {
        $words = preg_split('/\s+/', trim((string) $question->options->first()?->text), -1, PREG_SPLIT_NO_EMPTY);
        $mixed = collect($words)->shuffle($question->id)->values()->all();

        return count($words) > 1 && $mixed === $words ? array_reverse($words) : $mixed;
    }
}
