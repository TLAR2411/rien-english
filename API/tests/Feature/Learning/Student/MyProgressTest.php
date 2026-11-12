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

/** T28–T29 — My Progress: % of lessons done per skill / level, quiz scores, chart, topics to review. */
class MyProgressTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Level $a1;

    private Level $a2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->student = $this->userWithRole('student');
        $this->a1 = Level::factory()->create(['code' => 'A1', 'sort_order' => 1]);
        $this->a2 = Level::factory()->create(['code' => 'A2', 'sort_order' => 2]);
    }

    private function lesson(Level $level, string $skill = 'grammar', array $attributes = []): Lesson
    {
        return Lesson::factory()
            ->for(Course::factory()->for($level))
            ->create(['skill' => $skill, ...$attributes]);
    }

    private function finish(Lesson $lesson, ?User $as = null): void
    {
        ($as ?? $this->student)->lessonProgress()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);
    }

    private function attempt(Quiz $quiz, int $percent, ?User $as = null): QuizAttempt
    {
        return QuizAttempt::create([
            'user_id' => ($as ?? $this->student)->id,
            'quiz_id' => $quiz->id,
            'score' => $percent / 10,
            'total' => 10,
            'percent' => $percent,
            'passed' => $percent >= $quiz->pass_score,
            'started_at' => now(),
            'finished_at' => now(),
        ]);
    }

    private function progress()
    {
        Passport::actingAs($this->student);

        return $this->getJson('/api/web/student/progress')->assertOk();
    }

    public function test_it_shows_the_share_of_lessons_done_per_skill_and_level(): void
    {
        $grammar = [$this->lesson($this->a1), $this->lesson($this->a1), $this->lesson($this->a2)];
        $reading = $this->lesson($this->a2, 'reading');
        $this->finish($grammar[0]);
        $this->finish($reading);
        $this->finish($grammar[1], $this->userWithRole('student')); // someone else

        $response = $this->progress();

        $skills = collect($response->json('data.skills'))->keyBy('skill');
        $this->assertSame(['done' => 1, 'total' => 3, 'percent' => 33], collect($skills['grammar'])->only('done', 'total', 'percent')->all());
        $this->assertSame(['done' => 1, 'total' => 1, 'percent' => 100], collect($skills['reading'])->only('done', 'total', 'percent')->all());
        $this->assertSame(0, $skills['listening']['total']);

        $response
            ->assertJsonPath('data.levels.0.code', 'A1')
            ->assertJsonPath('data.levels.0.done', 1)
            ->assertJsonPath('data.levels.0.total', 2)
            ->assertJsonPath('data.levels.0.percent', 50)
            ->assertJsonPath('data.levels.1.code', 'A2')
            ->assertJsonPath('data.levels.1.done', 1)
            ->assertJsonPath('data.levels.1.total', 2);
    }

    public function test_hidden_lessons_do_not_count(): void
    {
        $this->finish($this->lesson($this->a1, 'grammar', ['status' => 'draft']));
        $this->lesson($this->a1);

        $this->progress()
            ->assertJsonPath('data.levels.0.done', 0)
            ->assertJsonPath('data.levels.0.total', 1);
    }

    public function test_quiz_scores_show_best_last_tries_and_date(): void
    {
        $quiz = Quiz::factory()->for($this->lesson($this->a1, 'grammar', ['title_en' => 'To be']))->create(['pass_score' => 70]);
        $this->attempt($quiz, 40);
        $this->travel(1)->minutes();
        $this->attempt($quiz, 90);
        $this->travel(1)->minutes();
        $last = $this->attempt($quiz, 60);
        $this->attempt($quiz, 100, $this->userWithRole('student')); // someone else

        $this->progress()
            ->assertJsonCount(1, 'data.quizzes')
            ->assertJsonPath('data.quizzes.0.lesson.title_en', 'To be')
            ->assertJsonPath('data.quizzes.0.lesson.level_code', 'A1')
            ->assertJsonPath('data.quizzes.0.best_percent', 90)
            ->assertJsonPath('data.quizzes.0.last_percent', 60)
            ->assertJsonPath('data.quizzes.0.attempts', 3)
            ->assertJsonPath('data.quizzes.0.passed', true)
            ->assertJsonPath('data.quizzes.0.last_attempt_id', $last->id)
            ->assertJsonStructure(['data' => ['quizzes' => [['last_at', 'pass_score']]]]);
    }

    public function test_the_quiz_taken_last_comes_first_and_hidden_lessons_are_left_out(): void
    {
        $older = Quiz::factory()->for($this->lesson($this->a1))->create();
        $newer = Quiz::factory()->for($this->lesson($this->a1))->create();
        $hidden = Quiz::factory()->for($this->lesson($this->a1, 'grammar', ['status' => 'draft']))->create();
        $this->attempt($older, 50);
        $this->travel(1)->minutes();
        $this->attempt($newer, 50);
        $this->attempt($hidden, 50);

        $this->progress()
            ->assertJsonCount(2, 'data.quizzes')
            ->assertJsonPath('data.quizzes.0.quiz_id', $newer->id)
            ->assertJsonPath('data.quizzes.1.quiz_id', $older->id);
    }

    /** T29 */
    public function test_the_chart_has_the_newest_tries_oldest_first(): void
    {
        config(['learning.progress.chart_attempts' => 3]);
        $quiz = Quiz::factory()->for($this->lesson($this->a1, 'grammar', ['title_en' => 'To be']))->create();
        foreach ([10, 20, 30, 40] as $percent) {
            $this->attempt($quiz, $percent);
            $this->travel(1)->minutes();
        }
        $this->attempt($quiz, 99, $this->userWithRole('student')); // someone else

        $this->progress()
            ->assertJsonCount(3, 'data.history')
            ->assertJsonPath('data.history.0.percent', 20)
            ->assertJsonPath('data.history.2.percent', 40)
            ->assertJsonPath('data.history.2.lesson.title_en', 'To be')
            ->assertJsonStructure(['data' => ['history' => [['id', 'percent', 'passed', 'finished_at']]]]);
    }

    /** T29 */
    public function test_topics_to_review_are_quizzes_not_passed_yet_weakest_first(): void
    {
        $passed = Quiz::factory()->for($this->lesson($this->a1))->create(['pass_score' => 70]);
        $weak = Quiz::factory()->for($this->lesson($this->a1))->create(['pass_score' => 70]);
        $weakest = Quiz::factory()->for($this->lesson($this->a1))->create(['pass_score' => 80]);
        $this->attempt($passed, 40);
        $this->attempt($passed, 70); // best = pass score → passed
        $this->attempt($weak, 60);
        $this->attempt($weakest, 50);

        $this->progress()
            ->assertJsonCount(2, 'data.to_review')
            ->assertJsonPath('data.to_review.0.quiz_id', $weakest->id)
            ->assertJsonPath('data.to_review.1.quiz_id', $weak->id)
            ->assertJsonPath('data.to_review.1.best_percent', 60);
    }

    public function test_guests_and_staff_cannot_open_it(): void
    {
        $this->getJson('/api/web/student/progress')->assertUnauthorized();

        Passport::actingAs($this->userWithRole('admin'));
        $this->getJson('/api/web/student/progress')->assertForbidden();
    }
}
