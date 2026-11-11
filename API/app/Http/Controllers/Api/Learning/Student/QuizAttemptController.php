<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Student\QuizAttemptResource;
use App\Http\Resources\Learning\Student\QuizResultResource;
use App\Models\Learning\QuizAttempt;
use App\Services\Learning\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "My Quizzes": the student's own quiz attempts, newest first, and one old result. */
class QuizAttemptController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $attempts = $request->user()->quizAttempts()
            ->with('quiz:id,lesson_id,title', 'quiz.lesson:id,slug,title_en,title_km')
            ->latest('finished_at')
            ->latest('id')
            ->paginate(config('learning.per_page'));

        return $this->paginated(QuizAttemptResource::collection($attempts));
    }

    /** One result with the correct answers. Another student's attempt → 404. */
    public function show(Request $request, QuizAttempt $attempt, StudentProgress $progress): JsonResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        $attempt->load(['answers', 'quiz.lesson', 'quiz.questions.options']);
        $attempt->next_lesson = $attempt->quiz->lesson ? $progress->lessonAfter($attempt->quiz->lesson)?->only(['id', 'slug', 'title_en', 'title_km']) : null;

        return $this->ok(new QuizResultResource($attempt));
    }
}
