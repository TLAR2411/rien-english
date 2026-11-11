<?php

namespace App\Http\Requests\Learning\Student;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A student sends their quiz answers: answers = [{ question_id, answer }].
 * `answer` is an option id, a typed text, or a list of words (word order).
 * An empty answer is allowed: it counts as wrong.
 */
class SubmitQuizRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array', 'max:200'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.answer' => ['nullable'],
            'answers.*.answer.*' => ['string', 'max:255'],
            'started_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('answers') as $index => $row) {
                $answer = $row['answer'] ?? null;
                $ok = $answer === null || is_int($answer) || is_array($answer) || (is_string($answer) && mb_strlen($answer) <= 255);
                if (! $ok) {
                    $validator->errors()->add("answers.{$index}.answer", 'This answer is not valid.');
                }
            }
        });
    }

    /** question_id => answer */
    public function answers(): array
    {
        return collect($this->validated('answers'))->pluck('answer', 'question_id')->all();
    }
}
