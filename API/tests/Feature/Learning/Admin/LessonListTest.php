<?php

namespace Tests\Feature\Learning\Admin;

use App\Enums\LessonSkill;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LessonListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    private function listIds(string $query = ''): array
    {
        return collect($this->getJson("/api/web/admin/lessons?{$query}")->assertOk()->json('data.data'))->pluck('id')->all();
    }

    public function test_list_does_not_send_the_lesson_text(): void
    {
        Lesson::factory()->create();

        $row = $this->getJson('/api/web/admin/lessons')->assertOk()->json('data.data.0');

        $this->assertArrayNotHasKey('content', $row);
        $this->assertArrayHasKey('course', $row);
    }

    public function test_filters_by_level_course_skill_status_and_search(): void
    {
        $course = Course::factory()->create();
        $grammar = Lesson::factory()->for($course)->create(['title_en' => 'Present Simple']);
        $reading = Lesson::factory()->for($course)->draft()->create(['skill' => LessonSkill::Reading, 'title_en' => 'My Family']);
        $other = Lesson::factory()->create();

        $this->assertSame([$grammar->id, $reading->id], $this->listIds("filter[level_id]={$course->level_id}"));
        $this->assertSame([$other->id], $this->listIds("filter[course_id]={$other->course_id}"));
        $this->assertSame([$reading->id], $this->listIds('filter[skill]=reading'));
        $this->assertSame([$reading->id], $this->listIds('filter[status]=draft'));
        $this->assertSame([$grammar->id], $this->listIds('filter[search]=present'));
    }

    public function test_filters_lessons_without_a_quiz(): void
    {
        $withQuiz = Lesson::factory()->create();
        Quiz::factory()->for($withQuiz)->create();
        $withoutQuiz = Lesson::factory()->create();

        $this->assertSame([$withoutQuiz->id], $this->listIds('filter[without_quiz]=1'));
    }

    public function test_list_is_paginated(): void
    {
        Lesson::factory()->count(3)->create();

        $this->getJson('/api/web/admin/lessons?limit=2')
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.meta.last_page', 2);
    }

    public function test_lesson_without_student_results_can_be_deleted(): void
    {
        $lesson = Lesson::factory()->create();

        $this->deleteJson("/api/web/admin/lessons/{$lesson->id}")->assertOk();

        $this->assertModelMissing($lesson);
    }

    public function test_lesson_whose_quiz_was_taken_cannot_be_deleted(): void
    {
        $quiz = Quiz::factory()->create();
        QuizAttempt::create(['user_id' => $this->userWithRole('student')->id, 'quiz_id' => $quiz->id]);

        $this->deleteJson("/api/web/admin/lessons/{$quiz->lesson_id}")->assertUnprocessable();
    }

    public function test_student_cannot_see_the_admin_lesson_list(): void
    {
        Passport::actingAs($this->userWithRole('student'));

        $this->getJson('/api/web/admin/lessons')->assertForbidden();
    }
}
