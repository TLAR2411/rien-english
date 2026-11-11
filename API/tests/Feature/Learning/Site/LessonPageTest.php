<?php

namespace Tests\Feature\Learning\Site;

use App\Enums\QuestionType;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use App\Models\Learning\Question;
use App\Models\Learning\QuestionOption;
use App\Models\Learning\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LessonPageTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lesson = Lesson::factory()
            ->has(LessonExample::factory()->count(2), 'examples')
            ->create(['content' => '<h2>Form</h2><p>I am a student.</p>', 'content_km' => '<p>ខ្ញុំជាសិស្ស។</p>']);

        $quiz = Quiz::factory()->for($this->lesson)->create();
        Question::factory()->for($quiz)->withOptions(2)->create(['prompt' => 'SECRET PROMPT', 'explanation' => 'SECRET EXPLANATION']);
        Question::factory()->for($quiz)->has(QuestionOption::factory()->correct()->state(['text' => 'TYPED ANSWER']), 'options')
            ->create(['type' => QuestionType::FillBlank]);
    }

    private function url(Lesson $lesson): string
    {
        return "/api/web/site/lessons/{$lesson->slug}";
    }

    public function test_a_guest_can_read_a_full_lesson(): void
    {
        $this->getJson($this->url($this->lesson))
            ->assertOk()
            ->assertJsonPath('data.title_en', $this->lesson->title_en)
            ->assertJsonPath('data.content_km', '<p>ខ្ញុំជាសិស្ស។</p>')
            ->assertJsonCount(2, 'data.examples')
            ->assertJsonPath('data.course.level.code', $this->lesson->course->level->code)
            ->assertJsonPath('data.quiz.questions_count', 2)
            ->assertJsonPath('data.completed_at', null);
    }

    public function test_the_lesson_never_sends_quiz_questions_or_answers(): void
    {
        $response = $this->getJson($this->url($this->lesson))->assertOk()->assertJsonMissingPath('data.quiz.questions');

        foreach (['is_correct', 'SECRET PROMPT', 'SECRET EXPLANATION', 'TYPED ANSWER'] as $secret) {
            $this->assertStringNotContainsString($secret, $response->getContent());
        }
    }

    public function test_draft_lessons_return_404(): void
    {
        $this->getJson($this->url(Lesson::factory()->draft()->create()))->assertNotFound();
        $this->getJson($this->url(Lesson::factory()->for(Course::factory()->draft())->create()))->assertNotFound();
        $this->getJson('/api/web/site/lessons/no-such-lesson')->assertNotFound();
    }

    public function test_a_student_can_mark_a_lesson_as_completed(): void
    {
        $this->seedRoles();
        $student = $this->userWithRole('student');
        Passport::actingAs($student);

        $first = $this->postJson("/api/web/student/lessons/{$this->lesson->id}/complete")->assertOk()->json('data.completed_at');
        $this->postJson("/api/web/student/lessons/{$this->lesson->id}/complete")->assertOk()->assertJsonPath('data.completed_at', $first);

        $this->assertSame(1, $student->lessonProgress()->count());
        $this->assertNotNull($this->getJson($this->url($this->lesson))->json('data.completed_at'));
    }

    public function test_only_students_can_mark_visible_lessons_as_completed(): void
    {
        $this->seedRoles();
        $this->postJson("/api/web/student/lessons/{$this->lesson->id}/complete")->assertUnauthorized();

        Passport::actingAs($this->userWithRole('teacher'));
        $this->postJson("/api/web/student/lessons/{$this->lesson->id}/complete")->assertForbidden();

        Passport::actingAs($this->userWithRole('student'));
        $draft = Lesson::factory()->draft()->create();
        $this->postJson("/api/web/student/lessons/{$draft->id}/complete")->assertNotFound();
    }
}
