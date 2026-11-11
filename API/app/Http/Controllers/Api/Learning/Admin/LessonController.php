<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\LessonRequest;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Resources\Learning\LessonResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends AdminController
{
    public const PERMISSION_GROUP = 'lessons';

    /** Short columns only: the list does not need the lesson text. */
    private const LIST_COLUMNS = ['id', 'course_id', 'skill', 'title_en', 'title_km', 'slug', 'sort_order', 'status', 'updated_at'];

    public function index(Request $request): JsonResponse
    {
        $lessons = Lesson::query()
            ->select(self::LIST_COLUMNS)
            ->with('course.level')
            ->withCount('examples')
            ->when($this->filter($request, 'level_id'), fn ($q, $levelId) => $q->whereRelation('course', 'level_id', $levelId))
            ->when($this->filter($request, 'course_id'), fn ($q, $courseId) => $q->where('course_id', $courseId))
            ->when($this->filter($request, 'skill'), fn ($q, $skill) => $q->where('skill', $skill))
            ->when($this->filter($request, 'status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->filter($request, 'without_quiz'), fn ($q) => $q->doesntHave('quiz'))
            ->when($this->filter($request, 'search'), fn ($q, $search) => $q->where(
                fn ($q) => $q->where('title_en', 'like', "%{$search}%")->orWhere('title_km', 'like', "%{$search}%")
            ))
            ->orderBy('course_id')
            ->ordered()
            ->paginate($this->perPage($request));

        return $this->paginated(LessonResource::collection($lessons));
    }

    /** Everything the editor needs. */
    public function show(Lesson $lesson): JsonResponse
    {
        return $this->ok(new LessonResource($lesson->load('course.level')));
    }

    public function store(LessonRequest $request): JsonResponse
    {
        return $this->created(new LessonResource(Lesson::create($request->validated())));
    }

    public function update(LessonRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson->update($request->validated());

        return $this->ok(new LessonResource($lesson), 'Saved.');
    }

    public function move(MoveRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(Lesson $lesson): JsonResponse
    {
        $hasAttempts = QuizAttempt::whereRelation('quiz', 'lesson_id', $lesson->id)->exists();
        abort_if($hasAttempts, 422, 'Students have taken this quiz. Set the lesson to Draft instead of deleting it.');

        $lesson->delete();

        return $this->ok(message: 'Deleted.');
    }
}
