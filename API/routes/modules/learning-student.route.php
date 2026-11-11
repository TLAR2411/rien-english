<?php

/*
| Student features — /api/web/student/...
| Already behind auth:api + role student (see routes/api.php).
*/

use App\Http\Controllers\Api\Learning\Student\LessonProgressController;
use Illuminate\Support\Facades\Route;

Route::post('lessons/{lesson}/complete', [LessonProgressController::class, 'complete'])->middleware('throttle:30,1');
