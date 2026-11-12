<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Lesson;
use App\Models\Learning\LessonWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T31 — "Words to know" of a lesson: add, list, edit, reorder, delete. */
class LessonWordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_teacher_can_add_list_edit_and_delete_words(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->create(['skill' => 'reading']);

        $id = $this->postJson("/api/web/admin/lessons/{$lesson->id}/words", [
            'word' => 'rice field', 'meaning_en' => 'land where rice grows', 'meaning_km' => 'វាលស្រែ',
            'example' => 'He works in the rice field.',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/web/admin/lessons/{$lesson->id}/words")
            ->assertOk()
            ->assertJsonPath('data.data.0.word', 'rice field')
            ->assertJsonPath('data.data.0.meaning_km', 'វាលស្រែ');

        $this->putJson("/api/web/admin/words/{$id}", ['word' => 'farmer', 'meaning_km' => 'កសិករ'])->assertOk();
        $this->assertSame('farmer', LessonWord::find($id)->word);

        $this->deleteJson("/api/web/admin/words/{$id}")->assertOk();
        $this->assertDatabaseMissing('lesson_words', ['id' => $id]);
    }

    public function test_words_can_be_reordered(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->create();
        [$a, $b] = LessonWord::factory()->for($lesson)->count(2)->create()->all();

        $this->patchJson("/api/web/admin/words/{$b->id}/move", ['direction' => 'up'])->assertOk();

        $this->assertSame([$b->id, $a->id], $lesson->words()->pluck('id')->all());
    }

    public function test_word_is_required_and_audio_must_be_a_real_link(): void
    {
        Passport::actingAs($this->userWithRole('teacher'));
        $lesson = Lesson::factory()->create();

        $this->postJson("/api/web/admin/lessons/{$lesson->id}/words", ['audio_url' => 'javascript:alert(1)'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['word', 'audio_url']);
    }

    public function test_words_are_deleted_with_their_lesson(): void
    {
        $word = LessonWord::factory()->create();

        $word->lesson->delete();

        $this->assertDatabaseMissing('lesson_words', ['id' => $word->id]);
    }

    public function test_students_cannot_change_words(): void
    {
        Passport::actingAs($this->userWithRole('student'));
        $lesson = Lesson::factory()->create();

        $this->postJson("/api/web/admin/lessons/{$lesson->id}/words", ['word' => 'farmer'])->assertForbidden();
        $this->assertSame(0, LessonWord::count());
    }
}
