<?php

/*
|--------------------------------------------------------------------------
| Learning platform settings
|--------------------------------------------------------------------------
| One place for the values you may want to change later.
*/

return [

    // Levels created by LevelSeeder (code => English / Khmer name).
    // LevelSeeder also creates one free "Grammar <code>" course per level.
    'levels' => [
        'A1' => ['name' => 'Beginner', 'name_km' => 'កម្រិតដំបូង'],
        'A2' => ['name' => 'Elementary', 'name_km' => 'កម្រិតបឋម'],
        'B1' => ['name' => 'Intermediate', 'name_km' => 'កម្រិតមធ្យម'],
        'B2' => ['name' => 'Upper Intermediate', 'name_km' => 'កម្រិតមធ្យមខ្ពស់'],
        'C1' => ['name' => 'Advanced', 'name_km' => 'កម្រិតខ្ពស់'],
    ],

    'quiz' => [
        // % needed to pass a new quiz (each quiz can change its own)
        'pass_score' => 70,
        // Stars on the result page: 3 stars from this %, 2 stars = passed, 1 star from this %
        'three_stars' => 90,
        'one_star' => 50,
        // Quiz submits allowed per minute for one student (stops answer-guessing scripts)
        'submit_per_minute' => (int) env('QUIZ_SUBMIT_PER_MINUTE', 10),
    ],

    // Longest lesson text accepted (characters of HTML, per language)
    'lesson_max_length' => 200000,

    // Files teachers upload (audio for lessons/examples, images in lesson text).
    // Saved on this disk under `folder` (run `php artisan storage:link` once).
    'uploads' => [
        'disk' => 'public',
        'folder' => 'learning',
        'types' => [
            'audio' => ['mimes' => ['mp3'], 'max_kb' => 10 * 1024],
            'image' => ['mimes' => ['jpg', 'jpeg', 'png', 'webp'], 'max_kb' => 2 * 1024],
        ],
    ],

    // Rows per page in admin lists
    'per_page' => 25,

];
