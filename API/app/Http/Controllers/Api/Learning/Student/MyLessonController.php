<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Student\DoneLessonResource;
use App\Models\Learning\Lesson;
use App\Services\Learning\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * "My lessons": the lessons the student finished, grouped by course
 * (easiest level first). Phase 9 adds the courses the student bought here.
 */
class MyLessonController extends Controller
{
    use ApiResponses;

    public function index(Request $request, StudentProgress $progress): JsonResponse
    {
        $courses = $progress->doneLessons($request->user())
            ->sortBy([
                fn (Lesson $a, Lesson $b) => $a->course->level?->sort_order <=> $b->course->level?->sort_order,
                fn (Lesson $a, Lesson $b) => [$a->course->sort_order, $a->course_id] <=> [$b->course->sort_order, $b->course_id],
                fn (Lesson $a, Lesson $b) => $a->sort_order <=> $b->sort_order,
            ])
            ->groupBy('course_id')
            ->map(fn ($lessons) => [
                'course' => $lessons->first()->course->only(['id', 'title', 'title_km']),
                'level' => $lessons->first()->course->level?->only(['id', 'code', 'name', 'name_km']),
                'lessons' => DoneLessonResource::collection($lessons->values()),
            ])
            ->values();

        return $this->ok($courses);
    }
}
