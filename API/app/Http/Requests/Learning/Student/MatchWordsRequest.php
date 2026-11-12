<?php

namespace App\Http\Requests\Learning\Student;

use App\Services\Learning\WordMatchGame;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A student sends a finished matching game: pairs = [{ word_id, token }].
 * - The lesson must be an open vocabulary lesson (else 404).
 * - One pair for every word of the game, each word and each meaning used once.
 * - Every word belongs to the lesson, every token is a meaning of this lesson.
 */
class MatchWordsRequest extends FormRequest
{
    /** @var array<int, int> word id => the word id behind the chosen meaning */
    private array $pairs = [];

    protected function prepareForValidation(): void
    {
        abort_unless($this->game()->isOpen($this->route('lesson')), 404);
    }

    public function rules(): array
    {
        $size = $this->game()->size($this->route('lesson'));

        return [
            'pairs' => ['required', 'array', "size:{$size}"],
            'pairs.*.word_id' => ['required', 'integer', 'distinct'],
            'pairs.*.token' => ['required', 'string', 'max:1000', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return ['pairs.size' => 'Match every word before you check.'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $lesson = $this->route('lesson');
            $wordIds = $this->game()->words($lesson)->modelKeys();

            foreach ($this->input('pairs') as $index => $pair) {
                $chosen = $this->game()->wordIdFromToken($lesson, $pair['token']);

                if (! in_array((int) $pair['word_id'], $wordIds, true)) {
                    $validator->errors()->add("pairs.{$index}.word_id", 'This word is not in the lesson.');
                } elseif ($chosen === null || ! in_array($chosen, $wordIds, true)) {
                    $validator->errors()->add("pairs.{$index}.token", 'This meaning is not in the lesson.');
                } else {
                    $this->pairs[(int) $pair['word_id']] = $chosen;
                }
            }

            if ($validator->errors()->isEmpty() && count(array_unique($this->pairs)) !== count($this->pairs)) {
                $validator->errors()->add('pairs', 'Use each meaning only once.');
            }
        });
    }

    /** @return array<int, int> */
    public function pairs(): array
    {
        return $this->pairs;
    }

    private function game(): WordMatchGame
    {
        return app(WordMatchGame::class);
    }
}
