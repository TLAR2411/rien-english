<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Student\SubmitQuizRequest;
use App\Http\Resources\Learning\Student\QuizResultResource;
use App\Http\Resources\Learning\Student\StudentQuizResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use App\Services\Learning\QuizGrader;
use Illuminate\Http\JsonResponse;

/**
 * Taking a quiz (logged-in students). The questions go to the browser WITHOUT
 * the correct answers; answers are graded here, on the server.
 */
class QuizController extends Controller
{
    use ApiResponses;

    /** The quiz of a lesson, by the lesson's slug: questions + options, no answers. */
    public function show(Lesson $lesson): JsonResponse
    {
        $quiz = $this->openQuiz($lesson->quiz);

        return $this->ok([
            ...(new StudentQuizResource($quiz))->resolve(),
            'lesson' => $lesson->only(['id', 'slug', 'title_en', 'title_km']),
        ]);
    }

    /** Grade the answers, save the attempt, return the result with the correct answers. */
    public function submit(SubmitQuizRequest $request, Quiz $quiz, QuizGrader $grader): JsonResponse
    {
        $quiz = $this->openQuiz($quiz);
        $attempt = $grader->submit($request->user(), $quiz, $request->answers(), $request->validated('started_at'));

        // No message: the page shows the result itself (no "Saved" pop-up)
        return $this->ok(new QuizResultResource($attempt->load('answers')->setRelation('quiz', $quiz)), code: 201);
    }

    /** Only quizzes of visible (published) lessons that have questions. */
    private function openQuiz(?Quiz $quiz): Quiz
    {
        abort_if(! $quiz || ! Lesson::visible()->whereKey($quiz->lesson_id)->exists(), 404);

        $quiz->load(['lesson', 'questions.options']);
        abort_if($quiz->questions->isEmpty(), 404);

        return $quiz;
    }
}
