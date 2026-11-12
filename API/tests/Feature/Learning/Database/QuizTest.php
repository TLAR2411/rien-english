<?php

namespace Tests\Feature\Learning\Database;

use App\Enums\QuestionType;
use App\Models\Learning\Lesson;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_has_questions_with_options(): void
    {
        $quiz = Quiz::factory()->create();
        Question::factory()->for($quiz)->withOptions(2)->create();

        $question = $quiz->questions->first();

        $this->assertSame(70, $quiz->fresh()->pass_score);
        $this->assertSame(QuestionType::MultipleChoice, $question->type);
        $this->assertCount(3, $question->options);
        $this->assertSame(1, $question->options->where('is_correct', true)->count());
        $this->assertTrue($quiz->lesson->quiz->is($quiz));
    }

    public function test_correct_answers_are_hidden_from_json(): void
    {
        $question = Question::factory()->withOptions()->create();

        $json = json_encode($question->load('options'));

        $this->assertStringNotContainsString('is_correct', $json);
    }

    public function test_a_lesson_has_only_one_quiz(): void
    {
        $lesson = Lesson::factory()->create();
        Quiz::factory()->for($lesson)->create();

        $this->expectException(QueryException::class);
        Quiz::factory()->for($lesson)->create();
    }

    public function test_question_types_know_if_the_student_picks_or_types(): void
    {
        $this->assertTrue(QuestionType::TrueFalse->isChoice());
        $this->assertFalse(QuestionType::FillBlank->isChoice());
    }
}
