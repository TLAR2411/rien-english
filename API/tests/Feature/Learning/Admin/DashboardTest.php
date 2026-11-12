<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function attempt(User $student, Quiz $quiz, int $percent, $at = null): void
    {
        QuizAttempt::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'total' => 10,
            'score' => $percent / 10,
            'percent' => $percent,
            'passed' => $percent >= 70,
            'finished_at' => $at ?? now(),
        ]);
    }

    public function test_dashboard_counts_students_and_teachers(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $this->makeUser($branch, [], 'student');
        $this->makeUser($branch, [], 'student');
        Passport::actingAs($this->makeUser($branch, [], 'teacher'));

        $this->getJson('/api/web/admin/dashboard')
            ->assertOk()
            ->assertJson(['status' => true, 'data' => ['students' => 2, 'teachers' => 1]]);
    }

    /** T30 */
    public function test_dashboard_shows_sign_ups_active_students_quizzes_and_popular_lessons(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $old = $this->makeUser($branch, ['created_at' => now()->subDays(30), 'last_login' => now()->subDays(20)], 'student');
        $this->makeUser($branch, ['created_at' => now()->subDays(30), 'last_login' => now()->subDay()], 'student'); // active: logged in
        $new = $this->makeUser($branch, [], 'student');

        $course = Course::factory()->create();
        $popular = Lesson::factory()->for($course)->create(['title_en' => 'To be']);
        $other = Lesson::factory()->for($course)->create();
        Lesson::factory()->for($course)->create(); // nobody used it
        $quiz = Quiz::factory()->for($popular)->create();

        foreach ([$old, $new] as $student) {
            $student->lessonProgress()->create(['lesson_id' => $popular->id, 'completed_at' => now()->subDays(20)]);
        }
        $new->lessonProgress()->create(['lesson_id' => $other->id, 'completed_at' => now()->subDays(20)]);
        $this->attempt($old, $quiz, 60, now()->subDays(10));
        $this->attempt($new, $quiz, 80); // new is also active (took a quiz today)

        Passport::actingAs($this->makeUser($branch, [], 'admin'));
        $this->getJson('/api/web/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('data.recent_days', 7)
            ->assertJsonPath('data.students', 3)
            ->assertJsonPath('data.new_students', 1)
            ->assertJsonPath('data.active_students', 2)
            ->assertJsonPath('data.quizzes_taken', 2)
            ->assertJsonPath('data.quizzes_taken_recent', 1)
            ->assertJsonPath('data.average_percent', 70)
            ->assertJsonCount(2, 'data.popular_lessons')
            ->assertJsonPath('data.popular_lessons.0.title_en', 'To be')
            ->assertJsonPath('data.popular_lessons.0.finished_count', 2)
            ->assertJsonPath('data.popular_lessons.0.attempts_count', 2)
            ->assertJsonPath('data.popular_lessons.1.id', $other->id)
            ->assertJsonPath('data.quizzes_passed', 1);
    }

    public function test_activity_has_one_row_per_day_with_quiz_tries_and_sign_ups(): void
    {
        $branch = $this->makeBranch($this->makeCompany());
        $student = $this->makeUser($branch, ['created_at' => now()->subDays(30)], 'student');
        $this->makeUser($branch, [], 'student'); // signed up today
        $quiz = Quiz::factory()->for(Lesson::factory()->for(Course::factory()))->create();
        $this->attempt($student, $quiz, 50);
        $this->attempt($student, $quiz, 90);
        $this->attempt($student, $quiz, 90, now()->subDays(2));
        $this->attempt($student, $quiz, 90, now()->subDays(40)); // too old for the chart

        Passport::actingAs($this->makeUser($branch, [], 'admin'));
        $this->getJson('/api/web/admin/dashboard')
            ->assertOk()
            ->assertJsonCount(14, 'data.activity')
            ->assertJsonPath('data.activity.13', ['date' => today()->toDateString(), 'quizzes' => 2, 'sign_ups' => 1])
            ->assertJsonPath('data.activity.11.quizzes', 1)
            ->assertJsonPath('data.activity.0.quizzes', 0);
    }
}
