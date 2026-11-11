<?php

namespace Tests\Feature\Learning\Site;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_sees_only_published_lessons_on_the_landing_page(): void
    {
        $published = Lesson::factory()->create(['title_en' => 'Published lesson']);
        Lesson::factory()->draft()->create(['title_en' => 'Draft lesson']);
        Lesson::factory()->for(Course::factory()->draft())->create(['title_en' => 'Lesson in a draft course']);

        $response = $this->getJson('/api/web/site/home')
            ->assertOk()
            ->assertJsonCount(1, 'data.latest')
            ->assertJsonPath('data.latest.0.slug', $published->slug)
            ->assertJsonPath('data.latest.0.level.code', $published->course->level->code);

        $this->assertStringNotContainsString('Draft lesson', $response->getContent());
        $this->assertStringNotContainsString('Lesson in a draft course', $response->getContent());

        $grammar = collect($response->json('data.skills'))->firstWhere('value', 'grammar');
        $this->assertSame(1, $grammar['lessons_count']);
        $this->assertSame(1, collect($response->json('data.levels'))->sum('lessons_count'));
    }

    public function test_lesson_of_the_day_and_a_sample_question_without_the_answer(): void
    {
        $lesson = Lesson::factory()->has(LessonExample::factory(), 'examples')->create();
        Question::factory()->for(Quiz::factory()->for($lesson))->withOptions(2)->create(['explanation' => 'SECRET EXPLANATION']);

        $response = $this->getJson('/api/web/site/home')
            ->assertOk()
            ->assertJsonCount(3, 'data.question.options')
            ->assertJsonPath('data.question.lesson.slug', $lesson->slug)
            ->assertJsonPath('data.featured.slug', $lesson->slug)
            ->assertJsonPath('data.featured.example.sentence', $lesson->examples->first()->sentence);

        $this->assertStringNotContainsString('is_correct', $response->getContent());
        $this->assertStringNotContainsString('SECRET EXPLANATION', $response->getContent());
    }

    public function test_the_landing_page_works_with_no_lessons(): void
    {
        $this->getJson('/api/web/site/home')
            ->assertOk()
            ->assertJsonCount(0, 'data.latest')
            ->assertJsonPath('data.featured', null)
            ->assertJsonPath('data.question', null);
    }
}
