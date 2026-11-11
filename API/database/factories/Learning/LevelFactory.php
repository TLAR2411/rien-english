<?php

namespace Database\Factories\Learning;

use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Learning\Level> */
class LevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('?#')),
            'name' => fake()->words(2, true),
        ];
    }
}
