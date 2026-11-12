<?php

namespace App\Services\Learning;

use App\Models\Learning\Lesson;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Numbers for the admin dashboard: students, sign-ups, who is learning, quizzes.
 * "Recent" = the last config('learning.dashboard.recent_days') days.
 */
class LearningStats
{
    public function summary(): array
    {
        $since = $this->since();

        return [
            'recent_days' => config('learning.dashboard.recent_days'),
            'students' => $this->students()->count(),
            'teachers' => User::whereHasRole('teacher')->count(),
            'new_students' => $this->students()->where('created_at', '>=', $since)->count(),
            'active_students' => $this->activeStudents($since),
            'quizzes_taken' => QuizAttempt::count(),
            'quizzes_taken_recent' => QuizAttempt::where('finished_at', '>=', $since)->count(),
            'quizzes_passed' => QuizAttempt::where('passed', true)->count(),
            'average_percent' => $this->roundOrNull(QuizAttempt::avg('percent')),
            'popular_lessons' => $this->popularLessons(),
            'activity' => $this->dailyActivity(),
        ];
    }

    /**
     * One row per day for the last config('learning.dashboard.activity_days') days, oldest first:
     * [date, quizzes (tries that day), sign_ups (new students that day)]. Days with nothing show 0.
     */
    private function dailyActivity(): Collection
    {
        $from = today()->subDays(config('learning.dashboard.activity_days') - 1);

        $quizzes = QuizAttempt::where('finished_at', '>=', $from)
            ->selectRaw('DATE(finished_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');
        $signUps = $this->students()->where('users.created_at', '>=', $from)
            ->selectRaw('DATE(users.created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, config('learning.dashboard.activity_days') - 1))->map(function (int $offset) use ($from, $quizzes, $signUps) {
            $day = $from->copy()->addDays($offset)->toDateString();

            return ['date' => $day, 'quizzes' => (int) ($quizzes[$day] ?? 0), 'sign_ups' => (int) ($signUps[$day] ?? 0)];
        });
    }

    /** Students who logged in, finished a lesson or took a quiz since $since. */
    private function activeStudents(Carbon $since): int
    {
        return $this->students()
            ->where(fn (Builder $q) => $q
                ->where('last_login', '>=', $since)
                ->orWhereHas('lessonProgress', fn (Builder $progress) => $progress->where('completed_at', '>=', $since))
                ->orWhereHas('quizAttempts', fn (Builder $attempt) => $attempt->where('finished_at', '>=', $since)))
            ->count();
    }

    /** Lessons most students finished (then: most quiz tries), with both numbers. */
    private function popularLessons(): Collection
    {
        return Lesson::query()
            ->select('id', 'course_id', 'slug', 'title_en', 'title_km', 'status')
            ->with('course:id,level_id', 'course.level:id,code')
            ->withCount(['progress as finished_count'])
            ->withCount(['quizAttempts as attempts_count'])
            ->having('finished_count', '>', 0)
            ->orHaving('attempts_count', '>', 0)
            ->orderByDesc('finished_count')
            ->orderByDesc('attempts_count')
            ->limit(config('learning.dashboard.popular_lessons'))
            ->get()
            ->map(fn (Lesson $lesson) => [
                ...$lesson->only(['id', 'slug', 'title_en', 'title_km', 'finished_count', 'attempts_count']),
                'status' => $lesson->status?->value,
                'level_code' => $lesson->course?->level?->code,
            ]);
    }

    private function students(): Builder
    {
        return User::whereHasRole(config('roles.student'));
    }

    private function since(): Carbon
    {
        return now()->subDays(config('learning.dashboard.recent_days'));
    }

    private function roundOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) round($value);
    }
}
