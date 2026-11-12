<?php

namespace Tests\Feature\Learning\Admin;

use App\Enums\LessonSkill;
use App\Models\Learning\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LessonStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function switchStatus(Lesson $lesson, string $status)
    {
        return $this->patchJson("/api/web/admin/lessons/{$lesson->id}/status", ['status' => $status]);
    }

    public function test_teacher_can_publish_and_unpublish_a_lesson(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->draft()->create();

        $this->switchStatus($lesson, 'published')->assertOk();
        $this->assertSame('published', $lesson->fresh()->getRawOriginal('status'));

        $this->switchStatus($lesson, 'draft')->assertOk();
        $this->assertSame('draft', $lesson->fresh()->getRawOriginal('status'));
    }

    public function test_status_must_be_draft_or_published(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->draft()->create();

        $this->switchStatus($lesson, 'archived')->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_listening_lesson_needs_audio_to_be_published(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->draft()->create(['skill' => LessonSkill::Listening, 'audio_url' => null]);

        $this->switchStatus($lesson, 'published')->assertUnprocessable();
        $this->assertSame('draft', $lesson->fresh()->getRawOriginal('status'));
    }

    public function test_student_cannot_switch_status(): void
    {
        Passport::actingAs($this->userWithRole('student'));
        $lesson = Lesson::factory()->draft()->create();

        $this->switchStatus($lesson, 'published')->assertForbidden();
    }
}
