<?php

/*
| Public website — /api/web/site/...
| No login: visitors read published lessons for free (PLAN.md golden rule 4).
| Only Lesson::visible() lessons are returned, and never quiz answers.
*/

use App\Http\Controllers\Api\Learning\Site\HomeController;
use App\Http\Controllers\Api\Learning\Site\LessonController;
use App\Http\Controllers\Api\Learning\Site\LevelController;
use Illuminate\Support\Facades\Route;

Route::get('home', HomeController::class);
Route::get('levels', LevelController::class);
Route::get('lessons', [LessonController::class, 'index']);
