<?php

/*
|--------------------------------------------------------------------------
| Learning platform settings
|--------------------------------------------------------------------------
| One place for the values you may want to change later.
*/

return [

    // Levels created by LevelSeeder (code => English / Khmer name).
    // LevelSeeder also creates one free course per level (e.g. A1 "English for Beginners").
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

    'writing' => [
        // Writings one student may send per minute
        'submit_per_minute' => (int) env('WRITING_SUBMIT_PER_MINUTE', 5),
        // Longest writing accepted (characters) and the highest word target a teacher can set
        'max_length' => 20000,
        'max_words' => 2000,
        // Most checklist items on one writing task
        'checklist_max' => 10,
        // Longest teacher feedback (characters)
        'feedback_max_length' => 5000,
    ],

    'matching' => [
        // Matching game of a vocabulary lesson: pairs per game (random words when there are more),
        // fewest words a lesson needs for the game, games one student may check per minute
        'max_pairs' => 8,
        'min_words' => 3,
        'submit_per_minute' => (int) env('MATCHING_SUBMIT_PER_MINUTE', 10),
    ],

    'progress' => [
        // Quiz tries shown in the "Scores over time" chart (the newest ones)
        'chart_attempts' => 30,
    ],

    'dashboard' => [
        // "New sign-ups", "active students" and "quizzes taken" count the last N days
        'recent_days' => 7,
        // Days shown in the "Activity" chart (quiz tries + sign-ups per day)
        'activity_days' => 14,
        // How many lessons the "Most popular lessons" list shows
        'popular_lessons' => 5,
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
