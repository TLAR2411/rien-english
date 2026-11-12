<?php

namespace Database\Factories\Learning;

use App\Enums\QuestionType;
use App\Models\Learning\QuestionOption;
use App\Models\Learning\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\Question> */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::MultipleChoice,
            'prompt' => fake()->sentence().'?',
            'explanation' => fake()->sentence(),
        ];
    }

    /** A multiple choice question with one correct option and $wrong wrong ones. */
    public function withOptions(int $wrong = 2): static
    {
        return $this
            ->has(QuestionOption::factory()->correct(), 'options')
            ->has(QuestionOption::factory()->count($wrong), 'options');
    }
}
