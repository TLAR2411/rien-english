<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Student\StudentLessonResource;
use App\Models\Learning\Lesson;
use Illuminate\Http\JsonResponse;

/** A lesson + quiz exactly as a student gets it — also when it is still a draft. */
class PreviewController extends Controller
{
    use ApiResponses;

    public function __invoke(Lesson $lesson): JsonResponse
    {
        return $this->ok(new StudentLessonResource(
            $lesson->load(['course.level', 'examples', 'words', 'quiz.questions.options'])
        ));
    }
}
