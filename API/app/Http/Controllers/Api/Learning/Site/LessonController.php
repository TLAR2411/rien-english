<?php

namespace App\Http\Controllers\Api\Learning\Site;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Site\LessonListRequest;
use App\Http\Resources\Learning\Site\LessonCardResource;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/** Public lessons (no login). Only Lesson::visible() lessons. */
class LessonController extends Controller
{
    use ApiResponses;

    private const PER_PAGE = 12;

    /** The lesson list: filter by skill and level, search by title, pages. Easiest level first. */
    public function index(LessonListRequest $request): JsonResponse
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
            // A1 before A2 …: by the level's sort order, then course, then lesson order
            ->orderBy(
                Course::query()
                    ->join('levels', 'levels.id', '=', 'courses.level_id')
                    ->select('levels.sort_order')
                    ->whereColumn('courses.id', 'lessons.course_id')
            )
            ->orderBy('course_id')
            ->ordered()
            ->paginate(self::PER_PAGE);

        return $this->paginated(LessonCardResource::collection($lessons));
    }
}
