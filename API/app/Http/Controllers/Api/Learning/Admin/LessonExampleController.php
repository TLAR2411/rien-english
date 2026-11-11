<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\LessonExampleRequest;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Resources\Learning\LessonExampleResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\Middleware;

/** Example sentences are part of a lesson: viewing needs view-lessons, any change needs edit-lessons. */
class LessonExampleController extends AdminController
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-lessons', only: ['index']),
            new Middleware('permission:edit-lessons', except: ['index']),
        ];
    }

    public function index(Lesson $lesson): JsonResponse
    {
        return $this->ok(LessonExampleResource::collection($lesson->examples));
    }

    public function store(LessonExampleRequest $request, Lesson $lesson): JsonResponse
    {
        return $this->created(new LessonExampleResource($lesson->examples()->create($request->validated())));
    }

    public function update(LessonExampleRequest $request, LessonExample $example): JsonResponse
    {
        $example->update($request->validated());

        return $this->ok(new LessonExampleResource($example), 'Saved.');
    }

    public function move(MoveRequest $request, LessonExample $example): JsonResponse
    {
        $example->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(LessonExample $example): JsonResponse
    {
        $example->delete();

        return $this->ok(message: 'Deleted.');
    }
}
