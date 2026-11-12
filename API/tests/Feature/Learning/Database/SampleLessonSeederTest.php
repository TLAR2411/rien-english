<?php

namespace Tests\Feature\Learning\Database;

use App\Enums\LessonSkill;
use App\Enums\QuestionType;
use App\Models\Learning\Lesson;
use Database\Seeders\Learning\LevelSeeder;
use Database\Seeders\Learning\SampleLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SampleLessonSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([LevelSeeder::class, SampleLessonSeeder::class]);
    }

    public function test_three_a1_grammar_lessons_follow_the_grammar_template(): void
    {
        $lessons = Lesson::with(['course.level', 'examples', 'quiz.questions.options'])
            ->where('skill', LessonSkill::Grammar)->ordered()->get();

        $this->assertSame(['to-be-am-is-are', 'present-simple', 'a-an-the'], $lessons->pluck('slug')->all());

        foreach ($lessons as $lesson) {
            foreach (['<h2>Explanation</h2>', '<h2>Form</h2>', '<table>', '<h2>When to use</h2>', '<h2>Common mistakes</h2>'] as $part) {
                $this->assertStringContainsString($part, $lesson->content, "{$lesson->slug} is missing {$part}");
            }

            $this->assertCount(10, $lesson->examples);
            $this->assertTrue($lesson->examples->every(fn ($example) => filled($example->translation_km)));

            $questions = $lesson->quiz->questions;
            $this->assertCount(10, $questions);
            $this->assertCount(5, $questions->pluck('type')->unique());

            foreach ($questions as $question) {
                $this->assertNotEmpty($question->explanation);
                $this->assertNotEmpty($question->explanation_km);
                $correct = $question->options->where('is_correct', true)->count();
                $question->type->isChoice()
                    ? $this->assertSame(1, $correct, "{$lesson->slug}: \"{$question->prompt}\" needs one correct answer")
                    : $this->assertSame($question->options->count(), $correct);
            }
        }
    }

    public function test_one_a1_course_holds_lessons_of_several_skills(): void
    {
        $lessons = Lesson::with(['course.level', 'examples', 'quiz.questions.options'])->ordered()->get();

        $this->assertSame(['english-for-beginners'], $lessons->pluck('course.slug')->unique()->values()->all());
        $this->assertSame(['grammar', 'vocabulary', 'reading', 'listening'], $lessons->pluck('skill.value')->unique()->values()->all());

        foreach ($lessons as $lesson) {
            $this->assertSame('A1', $lesson->course->level->code);
            $this->assertFalse($lesson->isPublished(), 'Sample lessons stay drafts until a teacher checks them.');
            $this->assertNotEmpty($lesson->title_km);
            $this->assertNotEmpty($lesson->content_km);
            $this->assertNotEmpty($lesson->examples);
            $this->assertNotEmpty($lesson->quiz->questions);

            foreach ($lesson->quiz->questions as $question) {
                $correct = $question->options->where('is_correct', true)->count();
                $question->type->isChoice()
                    ? $this->assertSame(1, $correct, "{$lesson->slug}: \"{$question->prompt}\" needs one correct answer")
                    : $this->assertSame($question->options->count(), $correct);
            }
        }
    }

    public function test_reading_lesson_has_a_text_words_to_know_and_a_quiz(): void
    {
        $lesson = Lesson::with(['words', 'quiz.questions'])->where('skill', LessonSkill::Reading)->sole();

        foreach (['<h2>Before you read</h2>', '<h2>Read the text</h2>'] as $part) {
            $this->assertStringContainsString($part, $lesson->content, "{$lesson->slug} is missing {$part}");
        }
        $this->assertGreaterThanOrEqual(5, $lesson->words->count());
        foreach ($lesson->words as $word) {
            $this->assertNotEmpty($word->meaning_en, "{$word->word} needs an English meaning");
            $this->assertNotEmpty($word->meaning_km, "{$word->word} needs a Khmer meaning");
        }
        $this->assertGreaterThanOrEqual(5, $lesson->quiz->questions->count());
    }

    public function test_listening_lesson_has_audio_a_transcript_and_a_quiz(): void
    {
        $lesson = Lesson::with('quiz.questions')->where('skill', LessonSkill::Listening)->sole();

        $this->assertSame('learning/audio/samples/at-the-market.m4a', $lesson->getRawOriginal('audio_url'));
        Storage::disk('public')->assertExists('learning/audio/samples/at-the-market.m4a');
        $this->assertStringContainsString('<strong>Dara:</strong>', $lesson->transcript);
        $this->assertStringContainsString('<h2>Before you listen</h2>', $lesson->content);
        $this->assertGreaterThanOrEqual(5, $lesson->quiz->questions->count());
    }

    public function test_seeder_can_run_again_without_duplicates(): void
    {
        $this->seed(SampleLessonSeeder::class);

        $this->assertSame(6, Lesson::count());
        $this->assertSame(44, Lesson::withCount('examples')->get()->sum('examples_count'));
        $this->assertSame(14, Lesson::withCount('words')->get()->sum('words_count'));
    }

    public function test_seeded_lessons_appear_in_admin(): void
    {
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));

        $this->getJson('/api/web/admin/lessons?filter[status]=draft')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 6);
    }

    public function test_word_order_questions_have_words_to_mix(): void
    {
        $question = Lesson::first()->quiz->questions->firstWhere('type', QuestionType::WordOrder);

        $this->assertGreaterThan(2, str_word_count($question->options->first()->text));
    }
}
