<?php

namespace Database\Seeders\Learning;

use App\Enums\PublishStatus;
use App\Models\Learning\Course;
use App\Models\Learning\Level;
use Illuminate\Database\Seeder;

/**
 * Levels from config/learning.php, each with one free course.
 * A course holds lessons of every skill (grammar, reading, listening, writing, vocabulary).
 * Safe to run again: courses are matched by slug.
 */
class LevelSeeder extends Seeder
{
    /** level code => course */
    private const COURSES = [
        'A1' => [
            'slug' => 'english-for-beginners',
            'title' => 'English for Beginners',
            'title_km' => 'ភាសាអង់គ្លេសសម្រាប់អ្នកចាប់ផ្តើម',
            'description' => 'Your first steps in English: simple grammar, everyday words and short texts about you, your family and your day.',
        ],
        'A2' => [
            'slug' => 'everyday-english',
            'title' => 'Everyday English',
            'title_km' => 'ភាសាអង់គ្លេសប្រចាំថ្ងៃ',
            'description' => 'English for daily life: shopping, travel, food and talking about the past and the future.',
        ],
        'B1' => [
            'slug' => 'english-for-real-life',
            'title' => 'English for Real Life',
            'title_km' => 'ភាសាអង់គ្លេសក្នុងជីវិតពិត',
            'description' => 'Understand longer texts, share your opinion and write clear emails and short stories.',
        ],
        'B2' => [
            'slug' => 'confident-english',
            'title' => 'Confident English',
            'title_km' => 'ភាសាអង់គ្លេសដោយទំនុកចិត្ត',
            'description' => 'Speak and write with confidence about work, study and the news.',
        ],
        'C1' => [
            'slug' => 'advanced-english',
            'title' => 'Advanced English',
            'title_km' => 'ភាសាអង់គ្លេសកម្រិតខ្ពស់',
            'description' => 'Natural, fluent English for university, work and formal writing.',
        ],
    ];

    public function run(): void
    {
        $order = 0;

        foreach (config('learning.levels') as $code => $names) {
            $level = Level::updateOrCreate(['code' => $code], [...$names, 'sort_order' => ++$order]);

            if (! $course = self::COURSES[$code] ?? null) {
                continue;
            }

            Course::updateOrCreate(
                ['slug' => $course['slug']],
                [...$course, 'level_id' => $level->id, 'is_free' => true, 'status' => PublishStatus::Published],
            );
        }
    }
}
