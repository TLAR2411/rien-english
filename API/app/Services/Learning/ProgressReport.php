<?php

namespace App\Services\Learning;

use App\Enums\LessonSkill;
use App\Models\Learning\Lesson;
use App\Models\Learning\Level;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

/**
 * The numbers behind "My Progress": how much of each skill / level a student finished,
 * and their quiz scores per lesson. Lessons count only while they are visible (published).
 */
class ProgressReport
{
    /** Every skill, in menu order: [skill, done, total, percent]. Skills without lessons show 0 / 0. */
    public function bySkill(User $student): Collection
    {
        $counts = $this->lessonCounts($student, 'lessons.skill')->keyBy('skill');

        return collect(LessonSkill::cases())->map(fn (LessonSkill $skill) => [
            'skill' => $skill->value,
            ...$this->share($counts->get($skill->value)),
        ]);
    }

    /** Every level, easiest first: [id, code, name, name_km, done, total, percent]. */
    public function byLevel(User $student): Collection
    {
        $counts = $this->lessonCounts($student, 'courses.level_id')->keyBy('level_id');

        return Level::query()->ordered()->get(['id', 'code', 'name', 'name_km'])->map(fn (Level $level) => [
            ...$level->only(['id', 'code', 'name', 'name_km']),
            ...$this->share($counts->get($level->id)),
        ]);
    }

    /**
     * One row per quiz the user took, last taken first: the quiz with its lesson,
     * best / last %, how many tries, when, and the id of the last try.
     * $visibleOnly = false also lists quizzes of hidden lessons (for teachers).
     */
    public function quizScores(User $user, bool $visibleOnly = true): Collection
    {
        $rows = $user->quizAttempts()
            ->when($visibleOnly, fn (Builder $q) => $q->whereHas('quiz.lesson', fn (Builder $lesson) => $lesson->visible()))
            ->groupBy('quiz_id')
            ->selectRaw('quiz_id, MAX(percent) as best_percent, COUNT(*) as attempts, MAX(id) as last_attempt_id, MAX(finished_at) as last_at')
            ->toBase()
            ->get();

        $lastPercent = QuizAttempt::whereIn('id', $rows->pluck('last_attempt_id'))->pluck('percent', 'id');
        $quizzes = Quiz::with('lesson:id,course_id,slug,skill,title_en,title_km', 'lesson.course:id,level_id', 'lesson.course.level:id,code')
            ->findMany($rows->pluck('quiz_id'), ['id', 'lesson_id', 'title', 'pass_score'])
            ->keyBy('id');

        return $rows
            ->map(fn (object $row) => $this->scoreRow($row, $quizzes->get($row->quiz_id), $lastPercent->get($row->last_attempt_id)))
            ->sortByDesc(fn (array $row) => [$row['last_at'], $row['last_attempt_id']])
            ->values();
    }

    /**
     * The newest quiz tries (config: learning.progress.chart_attempts), oldest first,
     * for the "Scores over time" chart. Only quizzes of visible lessons.
     */
    public function scoreHistory(User $student): Collection
    {
        return $student->quizAttempts()
            ->whereHas('quiz.lesson', fn (Builder $lesson) => $lesson->visible())
            ->with('quiz:id,lesson_id', 'quiz.lesson:id,title_en,title_km')
            ->latest('finished_at')
            ->latest('id')
            ->limit(config('learning.progress.chart_attempts'))
            ->get(['id', 'quiz_id', 'percent', 'passed', 'finished_at'])
            ->reverse()
            ->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'percent' => $attempt->percent,
                'passed' => $attempt->passed,
                'finished_at' => $attempt->finished_at,
                'lesson' => $attempt->quiz->lesson->only(['title_en', 'title_km']),
            ])
            ->values();
    }

    /** Quizzes whose best score is still under the pass score, weakest first. $scores = quizScores(). */
    public function topicsToReview(Collection $scores): Collection
    {
        return $scores
            ->reject(fn (array $row) => $row['passed'])
            ->sortBy('best_percent')
            ->values();
    }

    private function scoreRow(object $row, Quiz $quiz, ?int $lastPercent): array
    {
        $lesson = $quiz->lesson;

        return [
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'lesson' => $lesson ? [
                ...$lesson->only(['id', 'slug', 'title_en', 'title_km']),
                'skill' => $lesson->skill?->value,
                'level_code' => $lesson->course?->level?->code,
            ] : null,
            'pass_score' => $quiz->pass_score,
            'best_percent' => (int) $row->best_percent,
            'last_percent' => $lastPercent,
            'passed' => $row->best_percent >= $quiz->pass_score,
            'attempts' => (int) $row->attempts,
            'last_at' => $row->last_at,
            'last_attempt_id' => (int) $row->last_attempt_id,
        ];
    }

    /** Visible lessons grouped by $groupColumn, each with `total` and `done` (finished by the student). */
    private function lessonCounts(User $student, string $groupColumn): Collection
    {
        $alias = str($groupColumn)->afterLast('.')->toString();

        return Lesson::visible()
            ->join('courses', 'courses.id', '=', 'lessons.course_id')
            ->leftJoin('lesson_progress as done', fn (JoinClause $join) => $join
                ->on('done.lesson_id', '=', 'lessons.id')
                ->where('done.user_id', $student->id))
            ->groupBy($groupColumn)
            ->selectRaw("{$groupColumn} as {$alias}, COUNT(*) as total, COUNT(done.id) as done")
            ->toBase()
            ->get();
    }

    /** [done, total, percent] from a lessonCounts() row (0 / 0 when there is none). */
    private function share(?object $counts): array
    {
        $done = (int) ($counts->done ?? 0);
        $total = (int) ($counts->total ?? 0);

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total ? (int) round($done / $total * 100) : 0,
        ];
    }
}
