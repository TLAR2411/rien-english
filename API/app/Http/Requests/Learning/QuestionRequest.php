<?php

namespace App\Http\Requests\Learning;

use App\Enums\QuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A question with its options, saved together.
 * - Choice types (multiple choice, true/false, find the error): exactly one correct option.
 * - Typed types (fill blank, word order): every option is an accepted answer.
 * How many options each type needs: QuestionType::optionLimits().
 */
class QuestionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $type = QuestionType::tryFrom((string) $this->type);

        // Typed answers: every option the teacher wrote is a correct answer
        if ($type && ! $type->isChoice() && is_array($this->options)) {
            $this->merge(['options' => array_map(
                fn ($option) => [...(array) $option, 'is_correct' => true],
                $this->options,
            )]);
        }
    }

    public function rules(): array
    {
        [$min, $max] = $this->type()?->optionLimits() ?? [1, 10];

        return [
            'type' => ['required', Rule::enum(QuestionType::class)],
            'prompt' => ['required', 'string', 'max:2000'],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'explanation_km' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'between:1,100'],
            'options' => ['required', 'array', "min:{$min}", "max:{$max}"],
            'options.*.text' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'options.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->type()?->isChoice() || $validator->errors()->has('options*')) {
                    return;
                }

                $correct = collect($this->input('options'))->filter(fn ($o) => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOL));
                if ($correct->count() !== 1) {
                    $validator->errors()->add('options', 'Mark exactly one correct answer.');
                }
            },
        ];
    }

    public function type(): ?QuestionType
    {
        return QuestionType::tryFrom((string) $this->input('type'));
    }

    /** The question columns (without the options). */
    public function questionData(): array
    {
        return collect($this->validated())->except('options')->all();
    }

    /** @return list<array{text: string, is_correct: bool}> */
    public function optionsData(): array
    {
        return array_map(fn ($option) => [
            'text' => trim($option['text']),
            'is_correct' => (bool) $option['is_correct'],
        ], $this->validated('options'));
    }
}
