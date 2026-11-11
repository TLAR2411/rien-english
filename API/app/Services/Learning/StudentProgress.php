<?php

namespace App\Services\Learning;

use App\Http\Resources\Learning\Site\LessonCardResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * What one student has learned: finished lessons and best quiz scores.
 * Only lessons that are still visible (published) count.
 */
class StudentProgress
{
    /** lesson_id => completed_at, for the given lessons the student finished. */
    public function completedAt(User $student, iterable $lessonIds): Collection
    {
        return $student->lessonProgress()
            ->whereIn('lesson_id', collect($lessonIds))
            ->pluck('completed_at', 'lesson_id');
    }

    /**
     * The student's finished lessons, newest first, each with `completed_at`,
     * `best_percent` and `passed` (null until they take the lesson's quiz).
     */
    public function doneLessons(User $student, ?int $limit = null): EloquentCollection
    {
        $lessons = Lesson::visible()
            ->select(array_map(fn ($column) => "lessons.{$column}", LessonCardResource::COLUMNS))
            ->addSelect('lesson_progress.completed_at')
            ->join('lesson_progress', 'lesson_progress.lesson_id', '=', 'lessons.id')
            ->where('lesson_progress.user_id', $student->id)
            ->with('course.level')
            ->withExists('quiz')
            ->orderByDesc('lesson_progress.completed_at')
            ->orderByDesc('lessons.id')
            ->when($limit, fn (Builder $q) => $q->limit($limit))
            ->get();

        $scores = $this->bestScores($student, $lessons->modelKeys());

        return $lessons->each(function (Lesson $lesson) use ($scores) {
            $lesson->best_percent = $scores->get($lesson->id)?->best_percent;
            $lesson->passed = $scores->has($lesson->id) ? (bool) $scores->get($lesson->id)->passed : null;
        });
    }

    /** The first lesson (in learning order, in the student's level) they have not finished yet. */
    public function nextLesson(User $student): ?Lesson
    {
        return $this->inStudentLevel($student)
            ->select(LessonCardResource::COLUMNS)
            ->whereDoesntHave('progress', fn (Builder $q) => $q->where('user_id', $student->id))
            ->with('course.level')
            ->withExists('quiz')
            ->learningOrder()
            ->first();
    }

    /** The lesson after this one in learning order, with the same skill (e.g. the next grammar lesson). */
    public function lessonAfter(Lesson $lesson): ?Lesson
    {
        $ids = Lesson::visible()->where('skill', $lesson->skill)->learningOrder()->pluck('id');
        $position = $ids->search($lesson->id);
        $nextId = $position === false ? null : $ids->get($position + 1);

        return $nextId ? Lesson::find($nextId, ['id', 'slug', 'title_en', 'title_km']) : null;
    }

    /** Numbers for the dashboard. Lessons count in the student's level (all levels if none chosen). */
    public function stats(User $student): array
    {
        return [
            'lessons_done' => $this->inStudentLevel($student)->whereHas('progress', fn (Builder $q) => $q->where('user_id', $student->id))->count(),
            'lessons_total' => $this->inStudentLevel($student)->count(),
            'quizzes_passed' => $student->quizAttempts()->where('passed', true)->distinct()->count('quiz_id'),
        ];
    }

    /** Visible lessons of the level the student chose; every level when they chose none. */
    private function inStudentLevel(User $student): Builder
    {
        return Lesson::visible()->when(
            $student->level_id,
            fn (Builder $q, int $levelId) => $q->whereHas('course', fn (Builder $course) => $course->where('level_id', $levelId))
        );
    }

    /** lesson_id => {best_percent, passed} from the student's quiz attempts. */
    private function bestScores(User $student, array $lessonIds): Collection
    {
        if ($lessonIds === []) {
            return collect();
        }

        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quiz_attempts.user_id', $student->id)
            ->whereIn('quizzes.lesson_id', $lessonIds)
            ->groupBy('quizzes.lesson_id')
            ->selectRaw('quizzes.lesson_id, MAX(quiz_attempts.percent) as best_percent, MAX(quiz_attempts.passed) as passed')
            ->get()
            ->keyBy('lesson_id');
    }
}
