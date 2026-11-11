<?php

namespace App\Enums;

/**
 * Question types.
 * - Choice types: the student picks one option; the option with is_correct = true is right.
 * - Typed types: the student types / builds the answer; every option with is_correct = true
 *   is an accepted answer.
 */
enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case TrueFalse = 'true_false';
    case FillBlank = 'fill_blank';
    case WordOrder = 'word_order';
    case FindError = 'find_error';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple choice',
            self::TrueFalse => 'True / False',
            self::FillBlank => 'Fill in the blank',
            self::WordOrder => 'Word order',
            self::FindError => 'Find the error',
        };
    }

    /**
     * How many options a question of this type needs: [min, max].
     * Choice types: the options to pick from. Typed types: the accepted answers.
     */
    public function optionLimits(): array
    {
        return match ($this) {
            self::MultipleChoice => [2, 6],
            self::TrueFalse => [2, 2],
            self::FindError => [2, 10],
            self::FillBlank => [1, 10],
            self::WordOrder => [1, 5],
        };
    }

    /** The student picks one of the options (otherwise they type the answer). */
    public function isChoice(): bool
    {
        return in_array($this, [self::MultipleChoice, self::TrueFalse, self::FindError], true);
    }
}
