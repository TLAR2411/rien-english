<?php

namespace Database\Factories\Learning;

use App\Models\Learning\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\LessonExample> */
class LessonExampleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'sentence' => fake()->sentence(),
        ];
    }
}
