<?php

namespace Tests\Feature\Learning\Student;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Level;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** Student dashboard, "My lessons" and the ✓ Done mark in the lesson list. */
class MyLearningTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Lesson $toBe;

    private Lesson $family;

    private Lesson $past;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();

        $a1 = Course::factory()->for(Level::factory()->state(['code' => 'A1', 'sort_order' => 1]))->create(['title' => 'Basics']);
        $b1 = Course::factory()->for(Level::factory()->state(['code' => 'B1', 'sort_order' => 3]))->create(['title' => 'Past']);
        $this->toBe = Lesson::factory()->for($a1)->create(['title_en' => 'To be']);
        $this->family = Lesson::factory()->for($a1)->create(['title_en' => 'My family']);
        $this->past = Lesson::factory()->for($b1)->create(['title_en' => 'Past Simple']);

        $this->student = $this->userWithRole('student');
    }

    private function finish(Lesson $lesson, ?User $user = null, string $when = 'now'): void
    {
        ($user ?? $this->student)->lessonProgress()->create(['lesson_id' => $lesson->id, 'completed_at' => now()->modify($when)]);
    }

    private function attempt(Lesson $lesson, int $percent, bool $passed): void
    {
        $quiz = Quiz::firstWhere('lesson_id', $lesson->id) ?? Quiz::factory()->for($lesson)->create();
        QuizAttempt::create(['user_id' => $this->student->id, 'quiz_id' => $quiz->id, 'percent' => $percent, 'passed' => $passed]);
    }

    public function test_guests_and_staff_cannot_open_student_pages(): void
    {
        $this->getJson('/api/web/student/dashboard')->assertUnauthorized();
        $this->getJson('/api/web/student/my-lessons')->assertUnauthorized();

        Passport::actingAs($this->userWithRole('admin'));
        $this->getJson('/api/web/student/dashboard')->assertForbidden();
        $this->getJson('/api/web/student/my-lessons')->assertForbidden();
    }

    public function test_dashboard_shows_next_lesson_numbers_and_recent_lessons(): void
    {
        $this->finish($this->toBe, when: '-1 day');
        $this->attempt($this->toBe, 60, false);
        $this->attempt($this->toBe, 90, true);
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.next_lesson.slug', $this->family->slug) // first A1 lesson not done yet
            ->assertJsonPath('data.stats', ['lessons_done' => 1, 'lessons_total' => 3, 'quizzes_passed' => 1])
            ->assertJsonCount(1, 'data.recent')
            ->assertJsonPath('data.recent.0.slug', $this->toBe->slug)
            ->assertJsonPath('data.recent.0.best_percent', 90)
            ->assertJsonPath('data.recent.0.passed', true);
    }

    public function test_new_student_starts_at_the_first_lesson(): void
    {
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.next_lesson.slug', $this->toBe->slug)
            ->assertJsonPath('data.stats.lessons_done', 0)
            ->assertJsonCount(0, 'data.recent');
    }

    public function test_my_lessons_groups_own_finished_lessons_by_course(): void
    {
        $this->finish($this->past);
        $this->finish($this->toBe);
        $this->finish($this->family, $this->userWithRole('student')); // another student's lesson
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/my-lessons')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.course.title', 'Basics') // A1 first
            ->assertJsonPath('data.0.level.code', 'A1')
            ->assertJsonPath('data.0.lessons.0.slug', $this->toBe->slug)
            ->assertJsonPath('data.0.lessons.0.best_percent', null) // no quiz taken yet
            ->assertJsonCount(1, 'data.0.lessons')
            ->assertJsonPath('data.1.course.title', 'Past');
    }

    public function test_lessons_no_longer_published_are_not_shown(): void
    {
        $this->finish($this->toBe);
        $this->toBe->update(['status' => 'draft']);
        Passport::actingAs($this->student);

        $this->getJson('/api/web/student/my-lessons')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/web/student/dashboard')->assertJsonPath('data.stats.lessons_done', 0);
    }

    public function test_lesson_list_marks_finished_lessons_for_the_student_only(): void
    {
        $this->finish($this->toBe);

        $guest = collect($this->getJson('/api/web/site/lessons')->assertOk()->json('data.data'));
        $this->assertCount(3, $guest);
        $this->assertFalse($guest->contains(fn ($lesson) => array_key_exists('completed_at', $lesson)));

        Passport::actingAs($this->student);
        $lessons = collect($this->getJson('/api/web/site/lessons')->json('data.data'))->keyBy('slug');

        $this->assertNotNull($lessons[$this->toBe->slug]['completed_at']);
        $this->assertNull($lessons[$this->family->slug]['completed_at']);
    }

    public function test_student_chooses_a_level_and_the_dashboard_follows_it(): void
    {
        $b1 = $this->past->course->level_id;
        Passport::actingAs($this->student);

        $this->putJson('/api/web/student/level', ['level_id' => $b1])
            ->assertOk()
            ->assertJsonPath('data.code', 'B1');
        $this->assertSame($b1, $this->student->fresh()->level_id);

        $this->getJson('/api/web/student/dashboard')
            ->assertJsonPath('data.level.code', 'B1')
            ->assertJsonPath('data.next_lesson.slug', $this->past->slug) // not the A1 lessons
            ->assertJsonPath('data.stats.lessons_total', 1);
    }

    public function test_level_must_exist_and_needs_a_student(): void
    {
        $this->putJson('/api/web/student/level', ['level_id' => 1])->assertUnauthorized();

        Passport::actingAs($this->student);
        $this->putJson('/api/web/student/level', ['level_id' => 999])->assertJsonValidationErrors('level_id');
        $this->putJson('/api/web/student/level', [])->assertJsonValidationErrors('level_id');
    }
}
