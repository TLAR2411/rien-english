<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Core\Branch;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T30 — admin user page: a student's numbers, scores per quiz and every quiz try. */
class UserScoresTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $admin;

    private User $student;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->branch = $this->makeBranch($this->makeCompany());
        $this->admin = $this->makeUser($this->branch, [], 'admin');
        $this->student = $this->makeUser($this->branch, ['name_en' => 'Dara'], 'student');

        $lesson = Lesson::factory()->for(Course::factory())->create(['title_en' => 'To be']);
        $this->quiz = Quiz::factory()->for($lesson)->create(['pass_score' => 70]);
    }

    private function attempt(int $percent, ?User $as = null): QuizAttempt
    {
        return QuizAttempt::create([
            'user_id' => ($as ?? $this->student)->id,
            'quiz_id' => $this->quiz->id,
            'score' => $percent / 10,
            'total' => 10,
            'percent' => $percent,
            'passed' => $percent >= 70,
            'finished_at' => now(),
        ]);
    }

    public function test_admin_sees_a_students_numbers_and_scores(): void
    {
        $this->attempt(50);
        $this->attempt(90);
        $this->attempt(100, $this->makeUser($this->branch, [], 'student')); // someone else
        $this->quiz->lesson->update(['status' => 'draft']); // hidden lessons still show for the admin

        Passport::actingAs($this->admin);
        $this->getJson("/api/web/users/{$this->student->id}/learning")
            ->assertOk()
            ->assertJsonPath('data.user.name_en', 'Dara')
            ->assertJsonPath('data.user.role.name', 'student')
            ->assertJsonPath('data.stats.quizzes_taken', 2)
            ->assertJsonPath('data.stats.quizzes_passed', 1)
            ->assertJsonPath('data.stats.average_percent', 70)
            ->assertJsonPath('data.quizzes.0.lesson.title_en', 'To be')
            ->assertJsonPath('data.quizzes.0.best_percent', 90)
            ->assertJsonPath('data.quizzes.0.attempts', 2)
            ->assertJsonMissingPath('data.user.password');
    }

    public function test_admin_sees_every_quiz_try_newest_first(): void
    {
        $this->attempt(40);
        $this->travel(1)->minutes();
        $this->attempt(80);

        Passport::actingAs($this->admin);
        $this->getJson("/api/web/users/{$this->student->id}/quiz-attempts")
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.percent', 80)
            ->assertJsonPath('data.data.1.percent', 40)
            ->assertJsonPath('data.data.0.lesson.title_en', 'To be')
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_a_user_with_no_quizzes_has_empty_numbers(): void
    {
        Passport::actingAs($this->admin);
        $this->getJson("/api/web/users/{$this->student->id}/learning")
            ->assertOk()
            ->assertJsonPath('data.stats.quizzes_taken', 0)
            ->assertJsonPath('data.stats.average_percent', null)
            ->assertJsonPath('data.quizzes', []);
    }

    public function test_users_of_another_company_are_not_found(): void
    {
        $outsider = $this->userWithRole('student');

        Passport::actingAs($this->admin);
        $this->getJson("/api/web/users/{$outsider->id}/learning")->assertNotFound();
        $this->getJson("/api/web/users/{$outsider->id}/quiz-attempts")->assertNotFound();
    }

    public function test_teachers_students_and_guests_cannot_open_it(): void
    {
        $url = "/api/web/users/{$this->student->id}/learning";
        $this->getJson($url)->assertUnauthorized();

        foreach (['teacher', 'student'] as $role) {
            Passport::actingAs($this->makeUser($this->branch, [], $role));
            $this->getJson($url)->assertForbidden();
        }
    }
}
