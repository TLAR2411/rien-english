<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Learning\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "Mark as completed" on a lesson page (logged-in students only). */
class LessonProgressController extends Controller
{
    use ApiResponses;

    /** Marks the lesson done. Doing it again keeps the first date. */
    public function complete(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless(Lesson::visible()->whereKey($lesson->id)->exists(), 404);

        $progress = $request->user()->lessonProgress()->firstOrCreate(
            ['lesson_id' => $lesson->id],
            ['completed_at' => now()],
        );

        return $this->ok(['completed_at' => $progress->completed_at]);
    }
}
