<?php

namespace Tests\Feature\Learning\Site;

use App\Enums\PublishStatus;
use App\Models\Learning\Lesson;
use Database\Seeders\Learning\LevelSeeder;
use Database\Seeders\Learning\SampleLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * T31 — the sample reading lesson "My family" end to end:
 * a visitor reads the text and its words, a student takes the quiz and the server grades it.
 */
class ReadingLessonTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seed([LevelSeeder::class, SampleLessonSeeder::class]);

        // Samples are drafts until a teacher checks them
        $this->lesson = Lesson::where('slug', 'reading-my-family')->firstOrFail();
        $this->lesson->update(['status' => PublishStatus::Published]);
    }

    public function test_visitor_reads_the_text_and_the_words_without_login(): void
    {
        $response = $this->getJson('/api/web/site/lessons/reading-my-family')
            ->assertOk()
            ->assertJsonPath('data.skill', 'reading')
            ->assertJsonPath('data.words.0.word', 'farmer')
            ->assertJsonPath('data.words.0.meaning_km', 'កសិករ')
            ->assertJsonPath('data.quiz.questions_count', 5);

        $this->assertStringContainsString('My name is Sokha', $response->json('data.content'));
        $this->assertCount(8, $response->json('data.words'));

        // The quiz needs login: no questions or answers here
        $json = $response->getContent();
        $this->assertStringNotContainsString('is_correct', $json);
        $this->assertStringNotContainsString('Battambang"', $json);
        $this->assertArrayNotHasKey('questions', $response->json('data.quiz'));
    }

    public function test_reading_lessons_appear_in_the_reading_list(): void
    {
        $this->getJson('/api/web/site/lessons?skill=reading')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.slug', 'reading-my-family');
    }

    public function test_draft_reading_lesson_is_hidden(): void
    {
        $this->lesson->update(['status' => PublishStatus::Draft]);

        $this->getJson('/api/web/site/lessons/reading-my-family')->assertNotFound();
    }

    public function test_visitor_must_log_in_to_take_the_quiz(): void
    {
        $this->getJson('/api/web/student/lessons/reading-my-family/quiz')->assertUnauthorized();
    }

    public function test_student_takes_the_reading_quiz_and_the_server_grades_it(): void
    {
        Passport::actingAs($this->userWithRole('student'));

        $quiz = $this->getJson('/api/web/student/lessons/reading-my-family/quiz')
            ->assertOk()
            ->assertJsonCount(5, 'data.questions')
            ->json('data');
        $this->assertStringNotContainsString('is_correct', json_encode($quiz));

        // Right answers from the text, except the last one
        $correct = $this->lesson->quiz->questions()->with('options')->get()
            ->mapWithKeys(fn ($q) => [$q->id => $q->options->firstWhere('is_correct', true)]);
        $answers = collect($quiz['questions'])->map(fn ($question) => [
            'question_id' => $question['id'],
            'answer' => $question['type'] === 'fill_blank'
                ? 'Phnom Penh'
                : $correct[$question['id']]->id,
        ])->all();

        $this->postJson("/api/web/student/quizzes/{$quiz['id']}/submit", ['answers' => $answers])
            ->assertCreated()
            ->assertJsonPath('data.score', 4)
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.percent', 80)
            ->assertJsonPath('data.passed', true);
    }
}
