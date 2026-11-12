<?php

/*
| Student features — /api/web/student/...
| Already behind auth:api + role student (see routes/api.php).
*/

use App\Http\Controllers\Api\Learning\Student\DashboardController;
use App\Http\Controllers\Api\Learning\Student\LessonProgressController;
use App\Http\Controllers\Api\Learning\Student\LevelController;
use App\Http\Controllers\Api\Learning\Student\MyLessonController;
use App\Http\Controllers\Api\Learning\Student\ProgressController;
use App\Http\Controllers\Api\Learning\Student\QuizAttemptController;
use App\Http\Controllers\Api\Learning\Student\QuizController;
use App\Http\Controllers\Api\Learning\Student\WritingController;
use Illuminate\Support\Facades\Route;

Route::get('dashboard', DashboardController::class);
Route::put('level', [LevelController::class, 'update'])->middleware('throttle:30,1');
Route::get('my-lessons', [MyLessonController::class, 'index']);
Route::get('progress', ProgressController::class);
Route::post('lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])->middleware('throttle:30,1');

// Quizzes: questions without answers; answers graded on the server
Route::get('lessons/{lesson:slug}/quiz', [QuizController::class, 'show']);
Route::post('quizzes/{quiz}/submit', [QuizController::class, 'submit'])
    ->middleware('throttle:'.config('learning.quiz.submit_per_minute').',1');

// My Quizzes: the student's own attempts and old results
Route::get('quiz-attempts', [QuizAttemptController::class, 'index']);
Route::get('quiz-attempts/{attempt}', [QuizAttemptController::class, 'show']);

// Writing lessons: send a writing (waits for a teacher), see my writings for a lesson
// My Writing: every writing with the teacher's score + feedback
Route::get('writings', [WritingController::class, 'index']);
Route::get('writings/{writing}', [WritingController::class, 'mine']);
Route::get('lessons/{lesson:slug}/writing', [WritingController::class, 'show']);
Route::post('lessons/{lesson:slug}/writing', [WritingController::class, 'store'])
    ->middleware('throttle:'.config('learning.writing.submit_per_minute').',1');
