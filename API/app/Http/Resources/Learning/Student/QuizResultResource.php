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
 * `next_lesson` is sent when the controller sets it on the attempt.
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
            'stars' => $this->stars(),
            'pass_score' => $this->quiz->pass_score,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'lesson' => $lesson?->only(['id', 'slug', 'title_en', 'title_km']),
            // Set by the controller: the lesson to learn after this one
            'next_lesson' => $this->whenHas('next_lesson'),
            // The questions of this attempt (a question added to the quiz later is not in it)
            'questions' => $this->quiz->questions
                ->filter(fn (Question $question) => $answers->has($question->id))
                ->map(fn (Question $question) => $this->question($question, $answers->get($question->id)))
                ->values(),
        ];
    }

    /** 0–3 stars (config/learning.php → quiz). */
    private function stars(): int
    {
        return match (true) {
            $this->passed && $this->percent >= config('learning.quiz.three_stars') => 3,
            $this->passed => 2,
            $this->percent >= config('learning.quiz.one_star') => 1,
            default => 0,
        };
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
