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
    ],

    // Longest lesson text accepted (characters of HTML, per language)
    'lesson_max_length' => 200000,

    // Rows per page in admin lists
    'per_page' => 25,

];
