<?php

namespace Tests\Feature\Learning\Database;

use App\Enums\LessonSkill;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_belongs_to_a_course_and_has_ordered_examples(): void
    {
        $lesson = Lesson::factory()->create(['title_en' => 'Present Simple', 'skill' => LessonSkill::Grammar]);
        $second = LessonExample::factory()->for($lesson)->create(['sort_order' => 2]);
        $first = LessonExample::factory()->for($lesson)->create(['sort_order' => 1]);

        $this->assertSame('present-simple', $lesson->slug);
        $this->assertSame(LessonSkill::Grammar, $lesson->fresh()->skill);
        $this->assertSame([$first->id, $second->id], $lesson->examples->pluck('id')->all());
        $this->assertTrue($lesson->course->lessons->first()->is($lesson));
    }

    public function test_deleting_a_lesson_deletes_its_examples(): void
    {
        $example = LessonExample::factory()->create();

        $example->lesson->delete();

        $this->assertModelMissing($example);
    }

    public function test_a_course_with_lessons_cannot_be_deleted(): void
    {
        $course = Course::factory()->has(Lesson::factory())->create();

        $this->expectException(QueryException::class);
        $course->delete();
    }
}
