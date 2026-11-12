<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Lesson;
use App\Models\Learning\WritingSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T34 — teachers review students' writing; students see the score + feedback in My Writing. */
class WritingReviewTest extends TestCase
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
            'skill' => 'writing', 'slug' => 'about-my-family', 'title_en' => 'Writing: About my family',
            'checklist' => ['Use is / are.'], 'min_words' => 5, 'max_words' => 50,
            'sample_answer' => '<p>Sample.</p>',
        ]);
    }

    private function writing(array $attributes = []): WritingSubmission
    {
        return WritingSubmission::create([
            'user_id' => $this->student->id, 'lesson_id' => $this->lesson->id,
            'text' => 'There are four people in my family.', 'status' => 'pending', ...$attributes,
        ]);
    }

    public function test_students_cannot_open_writing_review(): void
    {
        Passport::actingAs($this->student);

        $this->getJson('/api/web/admin/writings')->assertForbidden();
        $this->putJson("/api/web/admin/writings/{$this->writing()->id}/review", ['score' => 90, 'feedback' => 'Good'])
            ->assertForbidden();
    }

    public function test_teacher_lists_waiting_writings_oldest_first(): void
    {
        $first = $this->writing();
        $second = $this->writing(['text' => 'My mother is a nurse.']);
        $this->writing(['status' => 'reviewed', 'score' => 70, 'feedback' => 'OK']);
        Passport::actingAs($this->userWithRole('teacher'));

        $this->getJson('/api/web/admin/writings?filter[status]=pending')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 2)
            ->assertJsonPath('data.data.0.id', $first->id)
            ->assertJsonPath('data.data.1.id', $second->id)
            ->assertJsonPath('data.data.0.student.id', $this->student->id)
            ->assertJsonPath('data.data.0.lesson.title_en', 'Writing: About my family')
            ->assertJsonPath('data.data.0.word_count', 7)
            ->assertJsonMissingPath('data.data.0.text');

        // All: waiting ones first
        $this->getJson('/api/web/admin/writings')->assertJsonPath('data.meta.total', 3)->assertJsonPath('data.data.0.status', 'pending');

        $this->getJson('/api/web/admin/writings?filter[search]='.urlencode($this->student->email))
            ->assertJsonPath('data.meta.total', 3);
        $this->getJson('/api/web/admin/writings?filter[search]=nobody-has-this-name')
            ->assertJsonPath('data.meta.total', 0);
    }

    public function test_teacher_opens_a_writing_with_the_task_and_the_next_one(): void
    {
        $earlier = $this->writing(['status' => 'reviewed', 'score' => 60, 'feedback' => 'Add more.']);
        $writing = $this->writing();
        $next = WritingSubmission::create([
            'user_id' => $this->userWithRole('student')->id, 'lesson_id' => $this->lesson->id,
            'text' => 'Hello', 'status' => 'pending',
        ]);
        Passport::actingAs($this->userWithRole('teacher'));

        $this->getJson("/api/web/admin/writings/{$writing->id}")
            ->assertOk()
            ->assertJsonPath('data.text', 'There are four people in my family.')
            ->assertJsonPath('data.lesson.checklist', ['Use is / are.'])
            ->assertJsonPath('data.lesson.sample_answer', '<p>Sample.</p>')
            ->assertJsonPath('data.earlier.0.id', $earlier->id)
            ->assertJsonPath('data.next_pending_id', $next->id)
            ->assertJsonPath('data.pending_count', 2);
    }

    public function test_teacher_gives_a_score_and_feedback(): void
    {
        $writing = $this->writing();
        $teacher = $this->userWithRole('teacher');
        Passport::actingAs($teacher);

        $this->putJson("/api/web/admin/writings/{$writing->id}/review", ['score' => 85, 'feedback' => "Good work!\nCheck your capital letters."])
            ->assertOk()
            ->assertJsonPath('data.status', 'reviewed')
            ->assertJsonPath('data.reviewer.id', $teacher->id)
            ->assertJsonPath('data.next_pending_id', null);

        $writing->refresh();
        $this->assertSame(85, $writing->score);
        $this->assertSame($teacher->id, $writing->reviewed_by);
        $this->assertNotNull($writing->reviewed_at);
    }

    public function test_review_needs_a_score_from_0_to_100_and_feedback(): void
    {
        $writing = $this->writing();
        Passport::actingAs($this->userWithRole('teacher'));

        $this->putJson("/api/web/admin/writings/{$writing->id}/review", ['score' => 101])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['score', 'feedback']);
        $this->assertSame('pending', $writing->fresh()->status->value);
    }

    public function test_student_sees_the_feedback_in_my_writing(): void
    {
        $writing = $this->writing(['status' => 'reviewed', 'score' => 85, 'feedback' => 'Good work!', 'reviewed_at' => now()]);
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/writings')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.data.0.score', 85)
            ->assertJsonPath('data.data.0.lesson.slug', 'about-my-family');

        $this->getJson("/api/web/student/writings/{$writing->id}")
            ->assertOk()
            ->assertJsonPath('data.feedback', 'Good work!')
            ->assertJsonPath('data.task.sample_answer', '<p>Sample.</p>')
            ->assertJsonPath('data.task.is_open', true);

        // The lesson page shows it too
        $this->getJson('/api/web/student/lessons/about-my-family/writing')
            ->assertJsonPath('data.submissions.0.status', 'reviewed')
            ->assertJsonPath('data.submissions.0.feedback', 'Good work!');
    }

    public function test_students_cannot_open_someone_elses_writing(): void
    {
        $writing = $this->writing();
        Passport::actingAs($this->userWithRole('student'));

        $this->getJson("/api/web/student/writings/{$writing->id}")->assertNotFound();
        $this->getJson('/api/web/student/writings')->assertJsonPath('data.meta.total', 0);
    }
}
