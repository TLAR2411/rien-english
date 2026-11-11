<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Support\YouTube;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** A teacher adds a YouTube link to a lesson; students watch it on the lesson page. */
class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return [
            'course_id' => Course::factory()->create()->id,
            'skill' => 'grammar',
            'title_en' => 'Present Simple',
            'status' => 'draft',
            ...$overrides,
        ];
    }

    private function actAsTeacher(): void
    {
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    public function test_teacher_can_add_a_youtube_link_to_a_lesson(): void
    {
        $this->actAsTeacher();
        $link = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        $id = $this->postJson('/api/web/admin/lessons', $this->payload(['video_url' => $link]))
            ->assertCreated()
            ->json('data.id');

        $this->getJson("/api/web/admin/lessons/{$id}")
            ->assertOk()
            ->assertJsonPath('data.video_url', $link);
    }

    public function test_only_youtube_video_links_are_accepted(): void
    {
        $this->actAsTeacher();

        foreach (['https://vimeo.com/123456', 'javascript:alert(1)', 'https://www.youtube.com/', 'https://evil.com/watch?v=dQw4w9WgXcQ'] as $bad) {
            $this->postJson('/api/web/admin/lessons', $this->payload(['video_url' => $bad]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['video_url']);
        }
    }

    public function test_the_lesson_page_gets_only_the_video_id(): void
    {
        $lesson = Lesson::factory()->create(['video_url' => 'https://youtu.be/dQw4w9WgXcQ?si=abc']);

        $this->getJson("/api/web/site/lessons/{$lesson->slug}")
            ->assertOk()
            ->assertJsonPath('data.youtube_id', 'dQw4w9WgXcQ');
    }

    public function test_a_lesson_without_a_video_has_no_video_id(): void
    {
        $lesson = Lesson::factory()->create(['video_url' => null]);

        $this->getJson("/api/web/site/lessons/{$lesson->slug}")
            ->assertOk()
            ->assertJsonPath('data.youtube_id', null);
    }

    public function test_video_id_is_read_from_every_kind_of_youtube_link(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=42s',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://youtube.com/live/dQw4w9WgXcQ',
        ] as $link) {
            $this->assertSame('dQw4w9WgXcQ', YouTube::videoId($link), $link);
        }

        $this->assertNull(YouTube::videoId('https://www.youtube.com/watch?v=short'));
        $this->assertNull(YouTube::videoId(null));
    }
}
