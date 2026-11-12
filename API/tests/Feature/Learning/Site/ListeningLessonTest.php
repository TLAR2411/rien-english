<?php

namespace Tests\Feature\Learning\Site;

use App\Enums\PublishStatus;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T32 — listening lessons: audio + transcript, the transcript opens after the quiz. */
class ListeningLessonTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $this->lesson = Lesson::factory()->create([
            'skill' => 'listening',
            'slug' => 'at-the-market',
            'audio_url' => 'https://cdn.example.com/market.mp3',
            'transcript' => '<p><strong>Seller:</strong> Good morning!</p><script>alert(1)</script>',
        ]);
    }

    public function test_visitor_gets_the_audio_and_the_transcript(): void
    {
        $this->getJson('/api/web/site/lessons/at-the-market')
            ->assertOk()
            ->assertJsonPath('data.skill', 'listening')
            ->assertJsonPath('data.audio_url', 'https://cdn.example.com/market.mp3')
            ->assertJsonPath('data.transcript', '<p><strong>Seller:</strong> Good morning!</p>')
            ->assertJsonPath('data.quiz_taken', false);
    }

    public function test_quiz_taken_is_true_after_the_student_tried_the_quiz(): void
    {
        $student = $this->userWithRole('student');
        $quiz = Quiz::factory()->for($this->lesson)->create();
        Passport::actingAs($student);

        $this->getJson('/api/web/site/lessons/at-the-market')->assertJsonPath('data.quiz_taken', false);

        QuizAttempt::create(['user_id' => $student->id, 'quiz_id' => $quiz->id, 'percent' => 40, 'passed' => false]);

        $this->getJson('/api/web/site/lessons/at-the-market')->assertJsonPath('data.quiz_taken', true);
    }

    public function test_listening_lesson_needs_audio_to_be_published(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $payload = [
            'course_id' => $this->lesson->course_id, 'skill' => 'listening', 'title_en' => 'At the bus stop',
            'transcript' => '<p>Hello.</p>',
        ];

        // A draft can wait for its audio
        $this->postJson('/api/web/admin/lessons', [...$payload, 'status' => PublishStatus::Draft->value])->assertCreated();

        $this->postJson('/api/web/admin/lessons', [...$payload, 'status' => PublishStatus::Published->value])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['audio_url']);
    }

    public function test_teacher_saves_the_transcript(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));

        $this->putJson("/api/web/admin/lessons/{$this->lesson->id}", [
            'course_id' => $this->lesson->course_id, 'skill' => 'listening', 'title_en' => 'At the market',
            'status' => 'published', 'audio_url' => 'https://cdn.example.com/market.mp3',
            'transcript' => '<p><strong>Dara:</strong> How much are the mangoes?</p>',
        ])->assertOk();

        $this->getJson("/api/web/admin/lessons/{$this->lesson->id}")
            ->assertJsonPath('data.transcript', '<p><strong>Dara:</strong> How much are the mangoes?</p>');
    }
}
