<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LessonEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    private function payload(array $overrides = []): array
    {
        return [
            'course_id' => Course::factory()->create()->id,
            'skill' => 'grammar',
            'title_en' => 'Present Simple',
            'title_km' => 'បច្ចុប្បន្នកាលសាមញ្ញ',
            'summary' => 'Talk about habits.',
            'content' => '<h2>Explanation</h2><p><strong>I work</strong> every day.</p>',
            'content_km' => '<p>យើងប្រើ…</p>',
            'status' => 'draft',
            ...$overrides,
        ];
    }

    public function test_teacher_can_create_a_lesson_with_formatted_text(): void
    {
        $this->postJson('/api/web/admin/lessons', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.slug', 'present-simple');

        $lesson = Lesson::first();
        $this->assertSame('<h2>Explanation</h2><p><strong>I work</strong> every day.</p>', $lesson->content);
        $this->assertSame('បច្ចុប្បន្នកាលសាមញ្ញ', $lesson->title_km);
    }

    public function test_dangerous_html_is_removed(): void
    {
        $this->postJson('/api/web/admin/lessons', $this->payload([
            'content' => '<p onclick="steal()">Hi</p><script>alert(1)</script><table><tbody><tr><td>I am</td></tr></tbody></table>',
        ]))->assertCreated();

        $content = Lesson::first()->content;
        $this->assertStringNotContainsString('script', $content);
        $this->assertStringNotContainsString('onclick', $content);
        $this->assertStringContainsString('<td>I am</td>', $content);
    }

    public function test_teacher_can_open_and_edit_a_lesson(): void
    {
        $lesson = Lesson::factory()->create();

        $this->getJson("/api/web/admin/lessons/{$lesson->id}")
            ->assertOk()
            ->assertJsonPath('data.content', $lesson->content);

        $this->putJson("/api/web/admin/lessons/{$lesson->id}", $this->payload(['course_id' => $lesson->course_id, 'title_en' => 'Changed', 'status' => 'published']))
            ->assertOk();

        $this->assertSame('Changed', $lesson->fresh()->title_en);
        $this->assertTrue($lesson->fresh()->isPublished());
    }

    public function test_lesson_needs_a_course_a_known_skill_and_a_title(): void
    {
        $this->postJson('/api/web/admin/lessons', ['skill' => 'dancing', 'status' => 'draft'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['course_id', 'skill', 'title_en']);
    }
}
