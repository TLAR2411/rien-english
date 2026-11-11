<?php

namespace Tests\Feature\Learning\Admin;

use App\Enums\QuestionType;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonExample;
use App\Models\Learning\Question;
use App\Models\Learning\QuestionOption;
use App\Models\Learning\Quiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PreviewTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));

        $this->lesson = Lesson::factory()->draft()->has(LessonExample::factory()->count(2), 'examples')->create();
        $quiz = Quiz::factory()->for($this->lesson)->create();
        Question::factory()->for($quiz)->withOptions(2)->create(['explanation' => 'SECRET EXPLANATION']);
        Question::factory()->for($quiz)->has(QuestionOption::factory()->correct()->state(['text' => 'TYPED ANSWER']), 'options')
            ->create(['type' => QuestionType::FillBlank]);
        Question::factory()->for($quiz)->has(QuestionOption::factory()->correct()->state(['text' => 'I am from Cambodia']), 'options')
            ->create(['type' => QuestionType::WordOrder]);
    }

    private function preview()
    {
        return $this->getJson("/api/web/admin/lessons/{$this->lesson->id}/preview")->assertOk();
    }

    public function test_draft_lesson_can_be_previewed_with_examples_and_quiz(): void
    {
        $this->preview()
            ->assertJsonPath('data.title_en', $this->lesson->title_en)
            ->assertJsonCount(2, 'data.examples')
            ->assertJsonCount(3, 'data.quiz.questions')
            ->assertJsonPath('data.course.level.code', $this->lesson->course->level->code);
    }

    public function test_preview_never_sends_correct_answers(): void
    {
        $json = $this->preview()->getContent();

        $this->assertStringNotContainsString('is_correct', $json);
        $this->assertStringNotContainsString('SECRET EXPLANATION', $json);
        $this->assertStringNotContainsString('TYPED ANSWER', $json);
        $this->assertStringNotContainsString('I am from Cambodia', $json);
    }

    public function test_choice_questions_get_options_and_word_order_gets_mixed_words(): void
    {
        $questions = collect($this->preview()->json('data.quiz.questions'))->keyBy('type');

        $this->assertCount(3, $questions['multiple_choice']['options']);
        $this->assertArrayNotHasKey('options', $questions['fill_blank']);

        $words = $questions['word_order']['words'];
        $this->assertEqualsCanonicalizing(['I', 'am', 'from', 'Cambodia'], $words);
        $this->assertNotSame(['I', 'am', 'from', 'Cambodia'], $words);
    }

    public function test_student_cannot_use_the_admin_preview(): void
    {
        Passport::actingAs($this->userWithRole('student'));

        $this->getJson("/api/web/admin/lessons/{$this->lesson->id}/preview")->assertForbidden();
    }
}
