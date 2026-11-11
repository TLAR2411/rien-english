<?php

namespace Database\Factories\Learning;

use App\Models\Learning\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\Quiz> */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'title' => fake()->sentence(3),
        ];
    }
}
