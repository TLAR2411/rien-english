<?php

/*
| Learning content admin — /api/web/admin/...
| Already behind auth:api + role admin|teacher (see routes/api.php).
| Each controller checks its own permissions (view-/add-/edit-/delete-<group>),
| see App\Http\Controllers\Concerns\ChecksCrudPermissions.
*/

use App\Http\Controllers\Api\Learning\Admin\CourseController;
use App\Http\Controllers\Api\Learning\Admin\DashboardController;
use App\Http\Controllers\Api\Learning\Admin\LessonController;
use App\Http\Controllers\Api\Learning\Admin\LessonExampleController;
use App\Http\Controllers\Api\Learning\Admin\LessonWordController;
use App\Http\Controllers\Api\Learning\Admin\LevelController;
use App\Http\Controllers\Api\Learning\Admin\PreviewController;
use App\Http\Controllers\Api\Learning\Admin\QuestionController;
use App\Http\Controllers\Api\Learning\Admin\QuizController;
use App\Http\Controllers\Api\Learning\Admin\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class);

/** list, all (for select boxes), create, update, move up/down, delete */
$crud = function (string $name, string $controller, string $parameter) {
    Route::get("{$name}-all", [$controller, 'all']);
    Route::patch("{$name}/{{$parameter}}/move", [$controller, 'move']);
    Route::apiResource($name, $controller)->except('show');
};

$crud('levels', LevelController::class, 'level');
$crud('courses', CourseController::class, 'course');

Route::patch('lessons/{lesson}/move', [LessonController::class, 'move']);
Route::get('lessons/{lesson}/preview', PreviewController::class)->middleware('permission:view-lessons');
Route::apiResource('lessons', LessonController::class);

// Example sentences: /lessons/{lesson}/examples (list, add), /examples/{example} (edit, move, delete)
Route::patch('examples/{example}/move', [LessonExampleController::class, 'move']);
Route::apiResource('lessons.examples', LessonExampleController::class)->shallow()->except('show');

// New words ("Words to know"): /lessons/{lesson}/words (list, add), /words/{word} (edit, move, delete)
Route::patch('words/{word}/move', [LessonWordController::class, 'move']);
Route::apiResource('lessons.words', LessonWordController::class)->shallow()->except('show');

// Quiz of a lesson (one per lesson) + list of all quizzes
Route::get('quizzes', [QuizController::class, 'index']);
Route::get('lessons/{lesson}/quiz', [QuizController::class, 'show']);
Route::put('lessons/{lesson}/quiz', [QuizController::class, 'save']);
Route::delete('lessons/{lesson}/quiz', [QuizController::class, 'destroy']);

// Questions with their options: /quizzes/{quiz}/questions (list, add), /questions/{question} (edit, move, delete)
Route::get('quizzes/{quiz}/questions', [QuestionController::class, 'index']);
Route::post('quizzes/{quiz}/questions', [QuestionController::class, 'store']);
Route::put('questions/{question}', [QuestionController::class, 'update']);
Route::patch('questions/{question}/move', [QuestionController::class, 'move']);
Route::delete('questions/{question}', [QuestionController::class, 'destroy']);

// Audio / image files (limits: config/learning.php → uploads)
Route::post('uploads', UploadController::class)->middleware('permission:add-lessons|edit-lessons');
