<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Site\LessonCardResource;
use App\Http\Resources\Learning\Student\DoneLessonResource;
use App\Services\Learning\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The student's home page: what to learn next, numbers, recent lessons. */
class DashboardController extends Controller
{
    use ApiResponses;

    private const RECENT = 5;

    public function __invoke(Request $request, StudentProgress $progress): JsonResponse
    {
        $student = $request->user();
        $next = $progress->nextLesson($student);

        return $this->ok([
            'next_lesson' => $next ? new LessonCardResource($next) : null,
            'stats' => $progress->stats($student),
            'recent' => DoneLessonResource::collection($progress->doneLessons($student, self::RECENT)),
        ]);
    }
}
