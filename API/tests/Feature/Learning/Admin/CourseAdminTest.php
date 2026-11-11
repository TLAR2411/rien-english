<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Level;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CourseAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    public function test_teacher_can_create_a_course(): void
    {
        $level = Level::factory()->create();

        $this->postJson('/api/web/admin/courses', [
            'level_id' => $level->id, 'title' => 'Grammar A1', 'title_km' => 'វេយ្យាករណ៍ A1', 'status' => 'draft',
        ])->assertCreated()->assertJsonPath('data.slug', 'grammar-a1');

        $this->assertDatabaseHas('courses', ['title' => 'Grammar A1', 'is_free' => true, 'status' => 'draft']);
    }

    public function test_course_needs_a_real_level_and_a_valid_status(): void
    {
        $this->postJson('/api/web/admin/courses', ['level_id' => 999, 'title' => 'X', 'status' => 'secret'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['level_id', 'status']);
    }

    public function test_paid_fields_cannot_be_set_yet(): void
    {
        $course = Course::factory()->create();

        $this->putJson("/api/web/admin/courses/{$course->id}", [
            'level_id' => $course->level_id, 'title' => 'New title', 'status' => 'published', 'is_free' => false, 'price' => 9,
        ])->assertOk();

        $this->assertTrue($course->fresh()->is_free);
        $this->assertSame('New title', $course->fresh()->title);
    }

    public function test_course_with_lessons_cannot_be_deleted(): void
    {
        $course = Course::factory()->has(Lesson::factory())->create();
        $empty = Course::factory()->create();

        $this->deleteJson("/api/web/admin/courses/{$course->id}")->assertUnprocessable();
        $this->deleteJson("/api/web/admin/courses/{$empty->id}")->assertOk();

        $this->assertModelMissing($empty);
    }

    public function test_list_filters_by_level(): void
    {
        $course = Course::factory()->create();
        Course::factory()->create();

        $this->getJson("/api/web/admin/courses?filter[level_id]={$course->level_id}")
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $course->id);
    }

    public function test_courses_move_inside_their_level(): void
    {
        $level = Level::factory()->create();
        [$a, $b] = Course::factory()->for($level)->count(2)->create()->all();

        $this->patchJson("/api/web/admin/courses/{$a->id}/move", ['direction' => 'down'])->assertOk();

        $this->assertSame([$b->id, $a->id], $level->courses()->ordered()->pluck('id')->all());
    }
}
