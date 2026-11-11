<?php

namespace Database\Factories\Learning;

use App\Enums\PublishStatus;
use App\Models\Learning\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\Course> */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'level_id' => Level::factory(),
            'title' => fake()->unique()->sentence(3),
            'status' => PublishStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PublishStatus::Draft]);
    }
}
