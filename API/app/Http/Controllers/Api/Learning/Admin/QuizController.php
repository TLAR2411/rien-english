<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\QuizRequest;
use App\Http\Resources\Learning\QuizResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/** One quiz per lesson: list all quizzes, open / save / delete the quiz of a lesson. */
class QuizController extends AdminController
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-quizzes', only: ['index', 'show']),
            new Middleware('permission:add-quizzes|edit-quizzes', only: ['save']),
            new Middleware('permission:delete-quizzes', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $quizzes = Quiz::query()
            ->with(['lesson' => fn ($q) => $q->select(['id', 'course_id', 'skill', 'title_en', 'title_km', 'slug', 'sort_order', 'status'])->with('course.level')])
            ->withCount('questions')
            ->when($this->filter($request, 'level_id'), fn ($q, $levelId) => $q->whereRelation('lesson.course', 'level_id', $levelId))
            ->when($this->filter($request, 'course_id'), fn ($q, $courseId) => $q->whereRelation('lesson', 'course_id', $courseId))
            ->when($this->filter($request, 'search'), fn ($q, $search) => $q->where(
                fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhereRelation('lesson', 'title_en', 'like', "%{$search}%")
            ))
            ->latest('updated_at')
            ->paginate($this->perPage($request));

        return $this->paginated(QuizResource::collection($quizzes));
    }

    /** The lesson's quiz with its questions and answers, or null when it has none yet. */
    public function show(Lesson $lesson): JsonResponse
    {
        $quiz = $lesson->quiz()->with('questions.options')->first();

        return $this->ok($quiz ? new QuizResource($quiz) : null);
    }

    /** Create the lesson's quiz, or update its settings. */
    public function save(QuizRequest $request, Lesson $lesson): JsonResponse
    {
        $quiz = Quiz::updateOrCreate(['lesson_id' => $lesson->id], $request->validated());

        return $this->ok(new QuizResource($quiz->load('questions.options')), 'Saved.');
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $quiz = $lesson->quiz()->firstOrFail();
        abort_if($quiz->attempts()->exists(), 422, 'Students have taken this quiz, so it cannot be deleted.');

        $quiz->delete();

        return $this->ok(message: 'Deleted.');
    }
}
