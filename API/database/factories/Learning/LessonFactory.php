<?php

namespace Database\Factories\Learning;

use App\Enums\LessonSkill;
use App\Enums\PublishStatus;
use App\Models\Learning\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\Lesson> */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'skill' => LessonSkill::Grammar,
            'title_en' => fake()->unique()->sentence(3),
            'summary' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => PublishStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft]);
    }
}
