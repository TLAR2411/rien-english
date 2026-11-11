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
use App\Http\Controllers\Api\Learning\Admin\LevelController;
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
Route::apiResource('lessons', LessonController::class);

// Example sentences: /lessons/{lesson}/examples (list, add), /examples/{example} (edit, move, delete)
Route::patch('examples/{example}/move', [LessonExampleController::class, 'move']);
Route::apiResource('lessons.examples', LessonExampleController::class)->shallow()->except('show');

// Audio / image files (limits: config/learning.php → uploads)
Route::post('uploads', UploadController::class)->middleware('permission:add-lessons|edit-lessons');
