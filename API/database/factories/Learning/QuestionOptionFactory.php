<?php

namespace Database\Factories\Learning;

use App\Models\Learning\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\QuestionOption> */
class QuestionOptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'text' => fake()->unique()->word(),
            'is_correct' => false,
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true]);
    }
}
