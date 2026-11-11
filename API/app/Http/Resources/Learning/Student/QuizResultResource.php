<?php

namespace App\Http\Resources\Learning\Student;

use App\Models\Learning\AttemptAnswer;
use App\Models\Learning\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A quiz AFTER the student submitted it: their score, and for every question
 * their answer, the correct answer and the explanation.
 * Needs the attempt with quiz.lesson, quiz.questions.options and answers loaded.
 *
 * @mixin \App\Models\Learning\QuizAttempt
 */
class QuizResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $answers = $this->answers->keyBy('question_id');
        $lesson = $this->quiz->lesson;

        return [
            'id' => $this->id,
            'quiz_id' => $this->quiz_id,
            'title' => $this->quiz->title,
            'score' => $this->score,
            'total' => $this->total,
            'percent' => $this->percent,
            'passed' => $this->passed,
            'pass_score' => $this->quiz->pass_score,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'lesson' => $lesson?->only(['id', 'slug', 'title_en', 'title_km']),
            'questions' => $this->quiz->questions->map(fn (Question $question) => $this->question($question, $answers->get($question->id)))->values(),
        ];
    }

    private function question(Question $question, ?AttemptAnswer $answer): array
    {
        $correct = $question->options->where('is_correct', true);

        return [
            ...(new StudentQuestionResource($question))->resolve(),
            'your_answer' => $answer?->answer,
            'is_correct' => (bool) $answer?->is_correct,
            // Choice types: the correct option id. Typed types: the accepted answers.
            'correct_option_id' => $question->type->isChoice() ? $correct->first()?->id : null,
            'correct_answers' => $question->type->isChoice() ? [] : $correct->pluck('text')->values(),
            'correct_text' => $correct->first()?->text,
            'explanation' => $question->explanation,
            'explanation_km' => $question->explanation_km,
        ];
    }
}
