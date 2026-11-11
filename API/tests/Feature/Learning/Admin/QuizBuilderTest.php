<?php

namespace Tests\Feature\Learning\Admin;

use App\Models\Learning\Lesson;
use App\Models\Learning\Question;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuizBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Passport::actingAs($this->userWithRole('teacher'));
    }

    private function makeQuiz(): Quiz
    {
        $lesson = Lesson::factory()->create();
        $this->putJson("/api/web/admin/lessons/{$lesson->id}/quiz", ['title' => 'Quiz: to be', 'pass_score' => 70])->assertOk();

        return $lesson->quiz;
    }

    /** One valid question of each type. */
    public static function questions(): array
    {
        $q = fn (string $type, string $prompt, array $options) => [[
            'type' => $type, 'prompt' => $prompt, 'points' => 1,
            'explanation' => 'Because …', 'explanation_km' => 'ព្រោះ …', 'options' => $options,
        ]];

        return [
            'multiple choice' => $q('multiple_choice', 'She ___ a teacher.', [
                ['text' => 'is', 'is_correct' => true], ['text' => 'am', 'is_correct' => false], ['text' => 'are', 'is_correct' => false],
            ]),
            'true / false' => $q('true_false', '"I is happy" is correct.', [
                ['text' => 'True', 'is_correct' => false], ['text' => 'False', 'is_correct' => true],
            ]),
            'fill blank' => $q('fill_blank', 'They ___ students.', [['text' => 'are'], ['text' => "'re"]]),
            'word order' => $q('word_order', 'Put the words in order.', [['text' => 'I am from Cambodia.']]),
            'find error' => $q('find_error', 'He are my brother.', [
                ['text' => 'He', 'is_correct' => false], ['text' => 'are', 'is_correct' => true], ['text' => 'my brother', 'is_correct' => false],
            ]),
        ];
    }

    public function test_teacher_creates_a_quiz_for_a_lesson_only_once(): void
    {
        $quiz = $this->makeQuiz();

        $this->putJson("/api/web/admin/lessons/{$quiz->lesson_id}/quiz", ['title' => 'Renamed', 'pass_score' => 80, 'time_limit_minutes' => 10])->assertOk();

        $this->assertSame(1, Quiz::count());
        $this->assertSame(80, $quiz->fresh()->pass_score);
    }

    #[DataProvider('questions')]
    public function test_each_question_type_can_be_saved(array $question): void
    {
        $quiz = $this->makeQuiz();

        $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", $question)
            ->assertCreated()
            ->assertJsonPath('data.type', $question['type'])
            ->assertJsonCount(count($question['options']), 'data.options');
    }

    public function test_typed_answers_are_all_accepted_answers(): void
    {
        $quiz = $this->makeQuiz();

        $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", self::questions()['fill blank'][0])->assertCreated();

        $this->assertSame([true, true], Question::first()->options->pluck('is_correct')->all());
    }

    public function test_teacher_builds_a_10_question_quiz_with_mixed_types(): void
    {
        $quiz = $this->makeQuiz();

        foreach (array_merge(array_values(self::questions()), array_values(self::questions())) as [$question]) {
            $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", $question)->assertCreated();
        }

        $saved = $this->getJson("/api/web/admin/lessons/{$quiz->lesson_id}/quiz")->assertOk()->json('data.questions');
        $this->assertCount(10, $saved);
        $this->assertCount(5, array_unique(array_column($saved, 'type')));
        $this->assertSame(range(1, 10), array_column($saved, 'sort_order'));
    }

    public function test_choice_question_needs_exactly_one_correct_answer(): void
    {
        $quiz = $this->makeQuiz();
        $question = self::questions()['multiple choice'][0];
        $question['options'][1]['is_correct'] = true;

        $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", $question)
            ->assertUnprocessable()->assertJsonValidationErrors('options');
    }

    public function test_question_needs_enough_options_for_its_type(): void
    {
        $quiz = $this->makeQuiz();
        $question = [...self::questions()['true / false'][0], 'options' => [['text' => 'True', 'is_correct' => true]]];

        $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", $question)
            ->assertUnprocessable()->assertJsonValidationErrors('options');
    }

    public function test_editing_a_question_replaces_its_options(): void
    {
        $quiz = $this->makeQuiz();
        $id = $this->postJson("/api/web/admin/quizzes/{$quiz->id}/questions", self::questions()['multiple choice'][0])->json('data.id');

        $this->putJson("/api/web/admin/questions/{$id}", self::questions()['true / false'][0])->assertOk();

        $this->assertSame(['True', 'False'], Question::find($id)->options->pluck('text')->all());
    }

    public function test_questions_can_be_reordered_and_deleted(): void
    {
        $quiz = $this->makeQuiz();
        [$a, $b] = Question::factory()->for($quiz)->withOptions()->count(2)->create()->all();

        $this->patchJson("/api/web/admin/questions/{$b->id}/move", ['direction' => 'up'])->assertOk();
        $this->assertSame([$b->id, $a->id], $quiz->questions()->pluck('id')->all());

        $this->deleteJson("/api/web/admin/questions/{$a->id}")->assertOk();
        $this->assertSame(0, $a->options()->count());
    }

    public function test_quiz_list_shows_question_counts(): void
    {
        $quiz = $this->makeQuiz();
        Question::factory()->for($quiz)->count(3)->create();

        $this->getJson('/api/web/admin/quizzes')
            ->assertOk()
            ->assertJsonPath('data.data.0.questions_count', 3)
            ->assertJsonPath('data.data.0.lesson.id', $quiz->lesson_id);
    }

    public function test_quiz_taken_by_students_cannot_be_deleted(): void
    {
        $quiz = $this->makeQuiz();
        $empty = Quiz::factory()->create();
        QuizAttempt::create(['user_id' => $this->userWithRole('student')->id, 'quiz_id' => $quiz->id]);

        $this->deleteJson("/api/web/admin/lessons/{$quiz->lesson_id}/quiz")->assertUnprocessable();
        $this->deleteJson("/api/web/admin/lessons/{$empty->lesson_id}/quiz")->assertOk();
    }

    public function test_student_cannot_open_the_quiz_builder(): void
    {
        $quiz = Quiz::factory()->create();
        Passport::actingAs($this->userWithRole('student'));

        $this->getJson("/api/web/admin/lessons/{$quiz->lesson_id}/quiz")->assertForbidden();
    }
}
