<?php

namespace Tests\Feature\Learning\Student;

use App\Enums\WritingStatus;
use App\Models\Learning\Lesson;
use App\Models\Learning\WritingSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T33 — writing lessons: read the task without login, students send a writing that waits for review. */
class WritingTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->student = $this->userWithRole('student');

        $this->lesson = Lesson::factory()->create([
            'skill' => 'writing',
            'slug' => 'about-my-family',
            'checklist' => ['Use is / are correctly.', 'End each sentence with a full stop.'],
            'min_words' => 5,
            'max_words' => 20,
            'sample_answer' => '<p>SECRET SAMPLE ANSWER</p>',
        ]);
    }

    private function send(string $text): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/web/student/lessons/about-my-family/writing', ['text' => $text]);
    }

    public function test_visitor_reads_the_task_but_never_gets_the_sample_answer(): void
    {
        $response = $this->getJson('/api/web/site/lessons/about-my-family')
            ->assertOk()
            ->assertJsonPath('data.skill', 'writing')
            ->assertJsonPath('data.checklist.0', 'Use is / are correctly.')
            ->assertJsonPath('data.min_words', 5)
            ->assertJsonPath('data.max_words', 20);

        $this->assertStringNotContainsString('SECRET SAMPLE ANSWER', $response->getContent());
    }

    public function test_guests_must_log_in_to_send_a_writing(): void
    {
        $this->getJson('/api/web/student/lessons/about-my-family/writing')->assertUnauthorized();
        $this->send('There are four people in my family.')->assertUnauthorized();
    }

    public function test_student_sends_a_writing_and_it_waits_for_review(): void
    {
        Passport::actingAs($this->student);

        // Before sending: no sample answer
        $this->getJson('/api/web/student/lessons/about-my-family/writing')
            ->assertOk()
            ->assertJsonCount(0, 'data.submissions')
            ->assertJsonPath('data.sample_answer', null);

        $this->send("  There are four people in my family.\nMy father is a driver.  ")
            ->assertCreated()
            ->assertJsonPath('data.submission.status', 'pending')
            ->assertJsonPath('data.submission.word_count', 12)
            ->assertJsonPath('data.submission.text', "There are four people in my family.\nMy father is a driver.")
            ->assertJsonPath('data.sample_answer', '<p>SECRET SAMPLE ANSWER</p>');

        $this->assertDatabaseHas('writing_submissions', [
            'user_id' => $this->student->id, 'lesson_id' => $this->lesson->id, 'status' => 'pending',
        ]);

        // After sending: the page shows "waiting for review" and the sample answer
        $this->getJson('/api/web/student/lessons/about-my-family/writing')
            ->assertJsonCount(1, 'data.submissions')
            ->assertJsonPath('data.submissions.0.status', 'pending')
            ->assertJsonPath('data.sample_answer', '<p>SECRET SAMPLE ANSWER</p>');
    }

    public function test_writing_must_fit_the_word_target(): void
    {
        Passport::actingAs($this->student);

        $this->send('Too short.')->assertUnprocessable()->assertJsonValidationErrors('text');
        $this->send(str_repeat('word ', 21))->assertUnprocessable()->assertJsonValidationErrors('text');
        $this->send('')->assertUnprocessable()->assertJsonValidationErrors('text');

        $this->assertSame(0, WritingSubmission::count());
    }

    public function test_only_one_writing_can_wait_for_review(): void
    {
        Passport::actingAs($this->student);
        $this->send('There are four people in my family.')->assertCreated();

        $this->send('My mother is a nurse at a hospital.')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');

        // After the teacher reviewed it, the student can write again
        WritingSubmission::query()->update(['status' => WritingStatus::Reviewed, 'score' => 80]);
        $this->send('My mother is a nurse at a hospital.')->assertCreated();
    }

    public function test_draft_or_non_writing_lessons_take_no_writing(): void
    {
        Passport::actingAs($this->student);

        $this->lesson->update(['status' => 'draft']);
        $this->send('There are four people in my family.')->assertNotFound();
        $this->getJson('/api/web/student/lessons/about-my-family/writing')->assertNotFound();

        Lesson::factory()->create(['skill' => 'grammar', 'slug' => 'to-be']);
        $this->postJson('/api/web/student/lessons/to-be/writing', ['text' => 'I am a student here.'])->assertNotFound();
    }

    public function test_students_only_see_their_own_writings(): void
    {
        WritingSubmission::create([
            'user_id' => $this->userWithRole('student')->id, 'lesson_id' => $this->lesson->id,
            'text' => 'Someone else', 'status' => 'pending',
        ]);
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/lessons/about-my-family/writing')
            ->assertJsonCount(0, 'data.submissions')
            ->assertJsonPath('data.sample_answer', null);
    }

    public function test_teacher_saves_the_writing_task(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $payload = [
            'course_id' => $this->lesson->course_id, 'skill' => 'writing', 'title_en' => 'About my day', 'status' => 'draft',
            'checklist' => ['Use the Present Simple.'], 'min_words' => 60, 'max_words' => 120,
            'sample_answer' => '<p>I get up at six.</p>',
        ];

        $id = $this->postJson('/api/web/admin/lessons', $payload)->assertCreated()->json('data.id');
        $this->getJson("/api/web/admin/lessons/{$id}")
            ->assertJsonPath('data.checklist', ['Use the Present Simple.'])
            ->assertJsonPath('data.max_words', 120)
            ->assertJsonPath('data.sample_answer', '<p>I get up at six.</p>');

        $this->postJson('/api/web/admin/lessons', [...$payload, 'min_words' => 150])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('max_words');
    }
}
