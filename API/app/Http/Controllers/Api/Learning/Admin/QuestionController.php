<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Actions\Learning\SaveQuestion;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Requests\Learning\QuestionRequest;
use App\Http\Resources\Learning\QuestionResource;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/** Questions are part of a quiz: viewing needs view-quizzes, every change needs edit-quizzes. */
class QuestionController extends AdminController
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-quizzes', only: ['index']),
            new Middleware('permission:edit-quizzes', except: ['index']),
        ];
    }

    /** Paginated, with the answers, for the questions table (AppCardTable). */
    public function index(Request $request, Quiz $quiz): JsonResponse
    {
        return $this->paginated(QuestionResource::collection(
            $quiz->questions()->with('options')->paginate($this->perPage($request))
        ));
    }

    public function store(QuestionRequest $request, Quiz $quiz, SaveQuestion $save): JsonResponse
    {
        return $this->created(new QuestionResource($save->handle($request, $quiz)));
    }

    public function update(QuestionRequest $request, Question $question, SaveQuestion $save): JsonResponse
    {
        return $this->ok(new QuestionResource($save->handle($request, $question->quiz, $question)), 'Saved.');
    }

    public function move(MoveRequest $request, Question $question): JsonResponse
    {
        $question->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return $this->ok(message: 'Deleted.');
    }
}
