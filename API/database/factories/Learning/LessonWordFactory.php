<?php

namespace Database\Factories\Learning;

use App\Models\Learning\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\LessonWord> */
class LessonWordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'word' => fake()->word(),
            'meaning_en' => fake()->sentence(4),
        ];
    }
}
