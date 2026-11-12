<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\LessonWordRequest;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Resources\Learning\LessonWordResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonWord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/** New words are part of a lesson: viewing needs view-lessons, any change needs edit-lessons. */
class LessonWordController extends AdminController
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-lessons', only: ['index']),
            new Middleware('permission:edit-lessons', except: ['index']),
        ];
    }

    /** Paginated, for the words table (AppCardTable). */
    public function index(Request $request, Lesson $lesson): JsonResponse
    {
        return $this->paginated(LessonWordResource::collection($lesson->words()->paginate($this->perPage($request))));
    }

    public function store(LessonWordRequest $request, Lesson $lesson): JsonResponse
    {
        return $this->created(new LessonWordResource($lesson->words()->create($request->validated())));
    }

    public function update(LessonWordRequest $request, LessonWord $word): JsonResponse
    {
        $word->update($request->validated());

        return $this->ok(new LessonWordResource($word), 'Saved.');
    }

    public function move(MoveRequest $request, LessonWord $word): JsonResponse
    {
        $word->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(LessonWord $word): JsonResponse
    {
        $word->delete();

        return $this->ok(message: 'Deleted.');
    }
}
