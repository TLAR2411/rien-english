<?php

namespace Database\Seeders\Learning;

use App\Enums\PublishStatus;
use App\Models\Learning\Course;
use App\Models\Learning\Level;
use Illuminate\Database\Seeder;

/**
 * Levels from config/learning.php, each with one free "Grammar <level>" course.
 * Safe to run again.
 */
class LevelSeeder extends Seeder
{
    public function run(): void
    {
        $order = 0;

        foreach (config('learning.levels') as $code => $names) {
            $level = Level::updateOrCreate(['code' => $code], [...$names, 'sort_order' => ++$order]);

            Course::firstOrCreate(
                ['level_id' => $level->id, 'title' => "Grammar {$code}"],
                [
                    'title_km' => "វេយ្យាករណ៍ {$code}",
                    'description' => "{$names['name']} English grammar, step by step.",
                    'is_free' => true,
                    'status' => PublishStatus::Published,
                ],
            );
        }
    }
}
