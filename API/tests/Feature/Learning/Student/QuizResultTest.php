<?php

namespace Tests\Feature\Learning\Student;

use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T26 — result page (stars, next lesson) and "My Quizzes" history. */
class QuizResultTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Lesson $first;

    private Lesson $second;

    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->student = $this->userWithRole('student');

        $course = Course::factory()->create();
        $this->first = Lesson::factory()->for($course)->create(['skill' => 'grammar', 'sort_order' => 1, 'title_en' => 'To be']);
        $this->second = Lesson::factory()->for($course)->create(['skill' => 'grammar', 'sort_order' => 2, 'slug' => 'present-simple']);
        $this->quiz = Quiz::factory()->for($this->first)->create(['title' => 'Quiz: to be', 'pass_score' => 70]);
        Question::factory()->for($this->quiz)->withOptions()->count(10)->create();
    }

    /** Submit with $right correct answers out of 10 → the attempt id. */
    private function takeQuiz(int $right, ?User $as = null): int
    {
        Passport::actingAs($as ?? $this->student);
        $answers = $this->quiz->questions()->with('options')->get()->values()->map(fn (Question $q, int $i) => [
            'question_id' => $q->id,
            'answer' => $q->options->firstWhere('is_correct', $i < $right)->id,
        ]);

        return $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => $answers->all()])
            ->assertCreated()
            ->json('data.id');
    }

    public function test_an_old_result_can_be_opened_with_stars_and_the_next_lesson(): void
    {
        $id = $this->takeQuiz(10);

        $this->getJson("/api/web/student/quiz-attempts/{$id}")
            ->assertOk()
            ->assertJsonPath('data.percent', 100)
            ->assertJsonPath('data.stars', 3)
            ->assertJsonPath('data.lesson.title_en', 'To be')
            ->assertJsonPath('data.next_lesson.slug', 'present-simple')
            ->assertJsonCount(10, 'data.questions')
            ->assertJsonStructure(['data' => ['questions' => [['your_answer', 'is_correct', 'correct_option_id', 'explanation', 'explanation_km']]]]);
    }

    public function test_stars_follow_the_score(): void
    {
        foreach ([10 => 3, 8 => 2, 7 => 2, 6 => 1, 4 => 0] as $right => $stars) {
            $id = $this->takeQuiz($right);
            $this->getJson("/api/web/student/quiz-attempts/{$id}")->assertJsonPath('data.stars', $stars);
        }
    }

    public function test_my_quizzes_lists_only_my_attempts_newest_first(): void
    {
        $this->takeQuiz(5, $this->userWithRole('student')); // someone else
        $this->takeQuiz(4);
        $this->travel(1)->minutes();
        $this->takeQuiz(9);

        $this->getJson('/api/web/student/quiz-attempts')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.data.0.percent', 90)
            ->assertJsonPath('data.data.0.passed', true)
            ->assertJsonPath('data.data.0.quiz_title', 'Quiz: to be')
            ->assertJsonPath('data.data.0.lesson.slug', $this->first->slug)
            ->assertJsonPath('data.data.1.percent', 40)
            ->assertJsonPath('data.meta.total', 2);
    }

    public function test_a_student_cannot_open_someone_elses_result(): void
    {
        $id = $this->takeQuiz(10, $this->userWithRole('student'));

        Passport::actingAs($this->student);
        $this->getJson("/api/web/student/quiz-attempts/{$id}")->assertNotFound();
    }

    public function test_guests_and_staff_cannot_see_results(): void
    {
        $this->getJson('/api/web/student/quiz-attempts')->assertUnauthorized();

        Passport::actingAs($this->userWithRole('admin'));
        $this->getJson('/api/web/student/quiz-attempts')->assertForbidden();
    }

    public function test_the_last_lesson_has_no_next_lesson(): void
    {
        $this->second->update(['status' => 'draft']);
        $id = $this->takeQuiz(10);

        $this->getJson("/api/web/student/quiz-attempts/{$id}")->assertJsonPath('data.next_lesson', null);
    }
}
