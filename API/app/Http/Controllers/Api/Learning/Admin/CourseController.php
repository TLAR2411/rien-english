<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\CourseRequest;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Resources\Learning\CourseResource;
use App\Models\Learning\Course;
use App\Models\Learning\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseController extends AdminController
{
    public const PERMISSION_GROUP = 'courses';

    public function index(Request $request): JsonResponse
    {
        $courses = Course::query()
            ->with('level')
            ->withCount('lessons')
            ->when($this->filter($request, 'level_id'), fn ($q, $levelId) => $q->where('level_id', $levelId))
            ->when($this->filter($request, 'status'), fn ($q, $status) => $q->where('status', $status))
            ->when($this->filter($request, 'search'), fn ($q, $search) => $q->where(
                fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('title_km', 'like', "%{$search}%")
            ))
            // Grouped by level (in level order), then by the course's own order
            ->orderBy(Level::select('sort_order')->whereColumn('levels.id', 'courses.level_id'))
            ->ordered()
            ->paginate($this->perPage($request));

        return $this->paginated(CourseResource::collection($courses));
    }

    /** Every course, for select boxes. */
    public function all(): JsonResponse
    {
        return $this->ok(CourseResource::collection(Course::with('level')->ordered()->get()));
    }

    public function store(CourseRequest $request): JsonResponse
    {
        return $this->created(new CourseResource(Course::create($request->validated())));
    }

    public function update(CourseRequest $request, Course $course): JsonResponse
    {
        $course->update($request->validated());

        return $this->ok(new CourseResource($course), 'Saved.');
    }

    public function move(MoveRequest $request, Course $course): JsonResponse
    {
        $course->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(Course $course): JsonResponse
    {
        abort_if($course->lessons()->exists(), 422, 'This course still has lessons. Move or delete them first.');

        $course->delete();

        return $this->ok(message: 'Deleted.');
    }
}
