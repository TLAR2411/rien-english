<?php

/*
| Public website — /api/web/site/...
| No login: visitors read published lessons for free (PLAN.md golden rule 4).
| Only Lesson::visible() lessons are returned, and never quiz answers.
*/

use App\Http\Controllers\Api\Learning\Site\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('home', HomeController::class);
