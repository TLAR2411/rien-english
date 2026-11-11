<?php

namespace App\Services\Learning;

use App\Enums\QuestionType;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Grades a quiz on the server (PLAN.md rule: never in the browser) and saves the attempt.
 *
 * Answers per question type:
 * - choice (multiple choice, true/false, find the error): the picked option id
 * - fill blank: the typed text
 * - word order: the words in the order the student put them
 * Typed answers are compared without caring about capitals, extra spaces or ’ vs '.
 */
class QuizGrader
{
    /**
     * @param  array<int, mixed>  $answers  question_id => answer. Questions not in this quiz are ignored.
     */
    public function submit(User $student, Quiz $quiz, array $answers, ?string $startedAt = null): QuizAttempt
    {
        $quiz->loadMissing('questions.options');

        return DB::transaction(function () use ($student, $quiz, $answers, $startedAt) {
            $rows = $quiz->questions->map(function (Question $question) use ($answers) {
                $answer = $this->clean($question, $answers[$question->id] ?? null);

                return [
                    'question_id' => $question->id,
                    'answer' => $answer,
                    'is_correct' => $this->isCorrect($question, $answer),
                    'points' => $question->points,
                ];
            });

            $total = (int) $rows->sum('points');
            $score = (int) $rows->where('is_correct', true)->sum('points');
            $percent = $total > 0 ? (int) round($score * 100 / $total) : 0;

            $attempt = $student->quizAttempts()->create([
                'quiz_id' => $quiz->id,
                'score' => $score,
                'total' => $total,
                'percent' => $percent,
                'passed' => $percent >= $quiz->pass_score,
                'started_at' => $startedAt ?? now(),
                'finished_at' => now(),
            ]);

            $attempt->answers()->createMany($rows->map(fn (array $row) => Arr::except($row, 'points'))->all());

            return $attempt;
        });
    }

    public function isCorrect(Question $question, mixed $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        $correct = $question->options->where('is_correct', true);

        if ($question->type->isChoice()) {
            return $correct->contains('id', $answer);
        }

        $given = self::normalize($question->type === QuestionType::WordOrder ? implode(' ', $answer) : $answer);

        return $correct->contains(fn ($option) => self::normalize($option->text) === $given);
    }

    /** Lower case, one space between words, no spaces at the ends, straight apostrophes. */
    public static function normalize(string $text): string
    {
        $text = str_replace(['’', '‘', '`'], "'", $text);

        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
    }

    /** Keeps only an answer of the right shape for the question (anything else = no answer). */
    private function clean(Question $question, mixed $answer): int|string|array|null
    {
        if ($question->type->isChoice()) {
            return is_numeric($answer) ? (int) $answer : null;
        }

        if ($question->type === QuestionType::WordOrder) {
            $words = is_array($answer) ? array_values(array_filter($answer, 'is_string')) : [];

            return $words ?: null;
        }

        return is_string($answer) && trim($answer) !== '' ? trim($answer) : null;
    }
}
