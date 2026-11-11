<?php

namespace Tests\Feature\Learning\Site;

use App\Enums\LessonSkill;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonListTest extends TestCase
{
    use RefreshDatabase;

    private function slugs(array $query = []): array
    {
        return collect($this->getJson('/api/web/site/lessons?'.http_build_query($query))->assertOk()->json('data.data'))
            ->pluck('slug')->all();
    }

    public function test_a_guest_sees_published_lessons_and_draft_lessons_are_hidden(): void
    {
        $published = Lesson::factory()->create();
        $draft = Lesson::factory()->draft()->create();
        $inDraftCourse = Lesson::factory()->for(Course::factory()->draft())->create();

        $slugs = $this->slugs();

        $this->assertSame([$published->slug], $slugs);
        $this->assertNotContains($draft->slug, $slugs);
        $this->assertNotContains($inDraftCourse->slug, $slugs);
    }

    public function test_the_list_has_no_lesson_text(): void
    {
        Lesson::factory()->create(['content' => '<p>FULL LESSON TEXT</p>']);

        $this->assertStringNotContainsString('FULL LESSON TEXT', $this->getJson('/api/web/site/lessons')->getContent());
    }

    public function test_filter_by_skill_level_and_search(): void
    {
        $a1 = Course::factory()->for(Level::factory()->state(['code' => 'A1']))->create();
        $b1 = Course::factory()->for(Level::factory()->state(['code' => 'B1']))->create();
        $toBe = Lesson::factory()->for($a1)->create(['title_en' => 'To be']);
        $reading = Lesson::factory()->for($a1)->create(['title_en' => 'My family', 'skill' => LessonSkill::Reading]);
        $past = Lesson::factory()->for($b1)->create(['title_en' => 'Past Simple', 'title_km' => 'អតីតកាល']);

        $this->assertSame([$reading->slug], $this->slugs(['skill' => 'reading']));
        $this->assertEqualsCanonicalizing([$toBe->slug, $reading->slug], $this->slugs(['level' => 'A1']));
        $this->assertSame([$past->slug], $this->slugs(['search' => 'past']));
        $this->assertSame([$past->slug], $this->slugs(['search' => 'អតីត']));
    }

    public function test_lessons_come_in_pages(): void
    {
        Lesson::factory()->count(14)->create();

        $this->getJson('/api/web/site/lessons?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.last_page', 2);
    }

    public function test_wrong_filters_are_rejected(): void
    {
        $this->getJson('/api/web/site/lessons?skill=cooking')->assertUnprocessable()->assertJsonValidationErrors('skill');
        $this->getJson('/api/web/site/lessons?level=Z9')->assertUnprocessable()->assertJsonValidationErrors('level');
    }

    public function test_levels_are_public(): void
    {
        Level::factory()->count(2)->create();

        $this->getJson('/api/web/site/levels')->assertOk()->assertJsonCount(2, 'data');
    }
}
