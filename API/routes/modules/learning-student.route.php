<?php

/*
| Student features — /api/web/student/...
| Already behind auth:api + role student (see routes/api.php).
*/

use App\Http\Controllers\Api\Learning\Student\DashboardController;
use App\Http\Controllers\Api\Learning\Student\LessonProgressController;
use App\Http\Controllers\Api\Learning\Student\MyLessonController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class);
Route::get('my-lessons', [MyLessonController::class, 'index']);
Route::post('lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])->middleware('throttle:30,1');
