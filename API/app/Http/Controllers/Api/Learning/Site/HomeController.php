<?php

namespace App\Http\Controllers\Api\Learning\Site;

use App\Enums\LessonSkill;
use App\Enums\QuestionType;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Site\LessonCardResource;
use App\Http\Resources\Learning\Student\StudentQuestionResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\Level;
use App\Models\Learning\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Everything the public landing page shows (no login).
 * Only published lessons of published courses (Lesson::visible()).
 */
class HomeController extends Controller
{
    use ApiResponses;

    private const LATEST_LESSONS = 5;

    public function __invoke(): JsonResponse
    {
        return $this->ok([
            'featured' => $this->lessonOfTheDay(),
            'levels' => $this->levels(),
            'skills' => $this->skills(),
            'latest' => LessonCardResource::collection(
                Lesson::visible()
                    ->select(LessonCardResource::COLUMNS)
                    ->with('course.level')
                    ->withCount('examples')
                    ->withExists('quiz')
                    ->latest()
                    ->limit(self::LATEST_LESSONS)
                    ->get()
            ),
            'question' => $this->sampleQuestion(),
        ]);
    }

    private function levels(): array
    {
        return Level::query()
            ->ordered()
            ->withCount(['lessons' => fn (Builder $lessons) => $lessons->visible()])
            ->get()
            ->map(fn (Level $level) => $level->only(['id', 'code', 'name', 'name_km', 'lessons_count']))
            ->all();
    }

    private function skills(): array
    {
        $counts = Lesson::visible()->toBase()->selectRaw('skill, count(*) as total')->groupBy('skill')->pluck('total', 'skill');

        return array_map(fn (LessonSkill $skill) => [
            'value' => $skill->value,
            'lessons_count' => (int) ($counts[$skill->value] ?? 0),
        ], LessonSkill::cases());
    }

    /**
     * "Lesson of the day": a different published lesson each day (same for everyone),
     * with its first example sentence — real content for visitors.
     */
    private function lessonOfTheDay(): ?array
    {
        $ids = Lesson::visible()->orderBy('id')->pluck('id');
        if ($ids->isEmpty()) {
            return null;
        }

        $lesson = Lesson::query()
            ->select(LessonCardResource::COLUMNS)
            ->with(['course.level', 'examples' => fn ($examples) => $examples->limit(1)])
            ->withExists('quiz')
            ->find($ids[now()->dayOfYear % $ids->count()]);

        return [
            ...(new LessonCardResource($lesson))->resolve(),
            'example' => $lesson->examples->first()?->only(['id', 'sentence', 'translation_km', 'audio_url']),
        ];
    }

    /** One real multiple-choice question — without the answer (it is graded in Laravel, after login). */
    private function sampleQuestion(): ?array
    {
        $question = Question::query()
            ->where('type', QuestionType::MultipleChoice)
            ->whereHas('quiz.lesson', fn (Builder $lesson) => $lesson->visible())
            ->with(['options', 'quiz.lesson:id,slug,title_en'])
            ->inRandomOrder()
            ->first();

        return $question ? [
            ...(new StudentQuestionResource($question))->resolve(),
            'lesson' => $question->quiz->lesson->only(['slug', 'title_en']),
        ] : null;
    }
}
