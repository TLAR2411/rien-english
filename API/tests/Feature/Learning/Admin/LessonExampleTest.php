<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LessonExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    public function test_teacher_can_add_list_edit_and_delete_examples(): void
    {
        $lesson = Lesson::factory()->create();
        $audio = Storage::disk('public')->url('learning/audio/2026/11/i-am.mp3');

        $id = $this->postJson("/api/web/admin/lessons/{$lesson->id}/examples", [
            'sentence' => 'I am a student.', 'translation_km' => 'ខ្ញុំជាសិស្ស។', 'audio_url' => $audio,
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/web/admin/lessons/{$lesson->id}/examples")
            ->assertOk()
            ->assertJsonPath('data.data.0.audio_url', $audio);

        // Saved as a short path, so a new domain keeps working
        $this->assertDatabaseHas('lesson_examples', ['id' => $id, 'audio_url' => 'learning/audio/2026/11/i-am.mp3']);

        $this->putJson("/api/web/admin/examples/{$id}", ['sentence' => 'You are a teacher.'])->assertOk();
        $this->assertSame('You are a teacher.', LessonExample::find($id)->sentence);

        $this->deleteJson("/api/web/admin/examples/{$id}")->assertOk();
        $this->assertDatabaseMissing('lesson_examples', ['id' => $id]);
    }

    public function test_examples_can_be_reordered(): void
    {
        $lesson = Lesson::factory()->create();
        [$a, $b] = LessonExample::factory()->for($lesson)->count(2)->create()->all();

        $this->patchJson("/api/web/admin/examples/{$b->id}/move", ['direction' => 'up'])->assertOk();

        $this->assertSame([$b->id, $a->id], $lesson->examples()->pluck('id')->all());
    }

    public function test_lesson_keeps_its_audio_and_external_links_stay_as_they_are(): void
    {
        $lesson = Lesson::factory()->create();
        $payload = [
            'course_id' => $lesson->course_id, 'skill' => 'listening', 'title_en' => 'At the market',
            'status' => 'draft', 'audio_url' => 'https://cdn.example.com/market.mp3',
        ];

        $this->putJson("/api/web/admin/lessons/{$lesson->id}", $payload)->assertOk();

        $this->assertSame('https://cdn.example.com/market.mp3', $lesson->fresh()->audio_url);
    }

    public function test_example_needs_a_sentence_and_a_real_link(): void
    {
        $lesson = Lesson::factory()->create();

        $this->postJson("/api/web/admin/lessons/{$lesson->id}/examples", ['audio_url' => 'javascript:alert(1)'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sentence', 'audio_url']);
    }
}
