<?php

namespace App\Http\Controllers\Api\Learning\Site;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Site\LessonListRequest;
use App\Http\Resources\Learning\Site\LessonCardResource;
use App\Http\Resources\Learning\Student\StudentLessonResource;
use App\Models\Learning\Lesson;
use App\Services\Learning\StudentProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Public lessons (no login). Only Lesson::visible() lessons. */
class LessonController extends Controller
{
    use ApiResponses;

    private const PER_PAGE = 12;

    /**
     * The lesson list: filter by skill and level, search by title, pages. Easiest level first.
     * A logged-in user also gets `completed_at` on each lesson they finished.
     */
    public function index(LessonListRequest $request, StudentProgress $progress): JsonResponse
    {
        $lessons = Lesson::visible()
            ->select(LessonCardResource::COLUMNS)
            ->with('course.level')
            ->withCount('examples')
            ->withExists('quiz')
            ->when($request->validated('skill'), fn (Builder $q, string $skill) => $q->where('skill', $skill))
            ->when($request->validated('level'), fn (Builder $q, string $code) => $q->whereHas('course.level', fn (Builder $level) => $level->where('code', $code)))
            ->when($request->validated('search'), fn (Builder $q, string $search) => $q->where(
                fn (Builder $q) => $q->where('title_en', 'like', "%{$search}%")->orWhere('title_km', 'like', "%{$search}%")
            ))
            ->learningOrder()
            ->paginate(self::PER_PAGE);

        // A logged-in student sees a ✓ on the lessons they finished
        if ($student = $request->user('api')) {
            $done = $progress->completedAt($student, $lessons->pluck('id'));
            $lessons->each(fn (Lesson $lesson) => $lesson->completed_at = $done->get($lesson->id));
        }

        return $this->paginated(LessonCardResource::collection($lessons));
    }

    /**
     * One lesson to read: text, examples, and only the size of its quiz —
     * never the questions or answers (those need login, see T24).
     * A logged-in student also gets `completed_at`.
     */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless(Lesson::visible()->whereKey($lesson->id)->exists(), 404);

        $lesson->load(['course.level', 'examples', 'quiz' => fn ($quiz) => $quiz->withCount('questions')]);
        $student = $request->user('api');

        return $this->ok([
            ...(new StudentLessonResource($lesson))->resolve($request),
            'completed_at' => $student?->lessonProgress()->where('lesson_id', $lesson->id)->first()?->completed_at,
        ]);
    }
}
