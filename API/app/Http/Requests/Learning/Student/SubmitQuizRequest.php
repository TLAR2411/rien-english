<?php

namespace App\Http\Requests\Learning\Student;

use App\Enums\QuestionType;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * A student sends their quiz answers: answers = [{ question_id, answer }].
 * `answer` is an option id, a typed text, or a list of words (word order).
 * An empty answer is allowed: it counts as wrong.
 *
 * Every answer must belong to this quiz: the question is one of its questions,
 * and a picked option is one of that question's options.
 */
class SubmitQuizRequest extends FormRequest
{
    /** A quiz of a hidden (draft) lesson, or with no questions, is "not found". */
    protected function prepareForValidation(): void
    {
        abort_unless($this->route('quiz')?->isOpen(), 404);
    }

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // fix the basic shape first
            }

            $questions = $this->quiz()->questions->keyBy('id');

            foreach ($this->input('answers') as $index => $row) {
                $question = $questions->get($row['question_id']);

                if (! $question) {
                    $validator->errors()->add("answers.{$index}.question_id", 'This question is not in this quiz.');
                } elseif (! $this->fitsQuestion($question, $row['answer'] ?? null)) {
                    $validator->errors()->add("answers.{$index}.answer", 'This answer is not valid.');
                }
            }
        });
    }

    /** The quiz from the URL, with its lesson, questions and options. */
    public function quiz(): Quiz
    {
        return $this->route('quiz')->loadMissing(['lesson', 'questions.options']);
    }

    /** question_id => answer */
    public function answers(): array
    {
        return collect($this->validated('answers'))->pluck('answer', 'question_id')->all();
    }

    /** Right shape for the question type: an option of this question, a short text, or a list of words. */
    private function fitsQuestion(Question $question, mixed $answer): bool
    {
        if ($answer === null) {
            return true;
        }

        if ($question->type->isChoice()) {
            return is_numeric($answer) && $question->options->contains('id', (int) $answer);
        }

        if ($question->type === QuestionType::WordOrder) {
            return is_array($answer) && array_is_list($answer);
        }

        return is_string($answer) && mb_strlen($answer) <= 255;
    }
}
