<?php

namespace Tests\Feature\Learning\Student;

use App\Enums\QuestionType;
use App\Models\Learning\Lesson;
use App\Models\Learning\Question;
use App\Models\Learning\QuestionOption;
use App\Models\Learning\Quiz;
use App\Models\Learning\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T24 — taking a quiz: no answers before submit, graded on the server. */
class QuizTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Lesson $lesson;

    private Quiz $quiz;

    /** @var array<string, Question> */
    private array $q = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->student = $this->userWithRole('student');

        $this->lesson = Lesson::factory()->create(['slug' => 'to-be']);
        $this->quiz = Quiz::factory()->for($this->lesson)->create(['pass_score' => 70]);

        $this->q['choice'] = $this->question(QuestionType::MultipleChoice, ['am' => true, 'is' => false, 'are' => false]);
        $this->q['true_false'] = $this->question(QuestionType::TrueFalse, ['True' => false, 'False' => true]);
        $this->q['find_error'] = $this->question(QuestionType::FindError, ['He' => false, 'are' => true, 'my friend.' => false]);
        $this->q['fill_blank'] = $this->question(QuestionType::FillBlank, ["isn't" => true, 'is not' => true]);
        $this->q['word_order'] = $this->question(QuestionType::WordOrder, ['Where are you from?' => true]);
    }

    private function question(QuestionType $type, array $options): Question
    {
        $question = Question::factory()->for($this->quiz)->create([
            'type' => $type, 'prompt' => "Prompt {$type->value}",
            'explanation' => "SECRET EXPLANATION {$type->value}", 'explanation_km' => 'ពន្យល់',
        ]);
        foreach ($options as $text => $correct) {
            QuestionOption::factory()->for($question)->create(['text' => $text, 'is_correct' => $correct]);
        }

        return $question->load('options');
    }

    private function optionId(string $key, string $text): int
    {
        return $this->q[$key]->options->firstWhere('text', $text)->id;
    }

    private function submit(array $answers): \Illuminate\Testing\TestResponse
    {
        $rows = collect($answers)->map(fn ($answer, $key) => ['question_id' => $this->q[$key]->id, 'answer' => $answer])->values()->all();

        return $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => $rows]);
    }

    public function test_guests_get_401(): void
    {
        $this->getJson('/api/web/student/lessons/to-be/quiz')->assertUnauthorized();
        $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => []])->assertUnauthorized();
    }

    public function test_quiz_questions_never_contain_the_answers(): void
    {
        Passport::actingAs($this->student);

        $response = $this->getJson('/api/web/student/lessons/to-be/quiz')
            ->assertOk()
            ->assertJsonPath('data.id', $this->quiz->id)
            ->assertJsonPath('data.lesson.slug', 'to-be')
            ->assertJsonCount(5, 'data.questions')
            ->assertJsonCount(3, 'data.questions.0.options');

        $json = $response->getContent();
        $this->assertStringNotContainsString('is_correct', $json);
        $this->assertStringNotContainsString('SECRET EXPLANATION', $json);
        $this->assertStringNotContainsString('correct', strtolower($json));
        // Typed answers are not sent either (word order only gets the words, mixed up)
        $this->assertStringNotContainsString("isn't", $json);
        $this->assertStringNotContainsString('Where are you from?', $json);
        $this->assertEqualsCanonicalizing(['Where', 'are', 'you', 'from?'], $response->json('data.questions.4.words'));
    }

    public function test_all_correct_answers_score_full_marks(): void
    {
        Passport::actingAs($this->student);

        $this->submit([
            'choice' => $this->optionId('choice', 'am'),
            'true_false' => $this->optionId('true_false', 'False'),
            'find_error' => $this->optionId('find_error', 'are'),
            'fill_blank' => '  IS   not ',
            'word_order' => ['Where', 'are', 'you', 'from?'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.score', 5)
            ->assertJsonPath('data.total', 5)
            ->assertJsonPath('data.percent', 100)
            ->assertJsonPath('data.passed', true);

        $attempt = QuizAttempt::sole();
        $this->assertSame($this->student->id, $attempt->user_id);
        $this->assertSame(5, $attempt->answers()->where('is_correct', true)->count());
    }

    public function test_wrong_and_missing_answers_score_zero(): void
    {
        Passport::actingAs($this->student);

        $this->submit([
            'choice' => $this->optionId('choice', 'is'),
            'true_false' => $this->optionId('true_false', 'True'),
            'find_error' => $this->optionId('find_error', 'He'),
            'fill_blank' => 'not',
            // word_order: not answered
        ])
            ->assertCreated()
            ->assertJsonPath('data.score', 0)
            ->assertJsonPath('data.percent', 0)
            ->assertJsonPath('data.passed', false);

        $this->assertSame(5, QuizAttempt::sole()->answers()->count());
    }

    public function test_each_type_is_graded_on_its_own(): void
    {
        Passport::actingAs($this->student);

        // Words in the wrong order are wrong; a curly apostrophe still matches
        $this->submit([
            'choice' => $this->optionId('choice', 'am'),
            'fill_blank' => 'isn’t',
            'word_order' => ['are', 'you', 'Where', 'from?'],
        ])
            ->assertJsonPath('data.score', 2)
            ->assertJsonPath('data.percent', 40)
            ->assertJsonPath('data.questions.0.is_correct', true)
            ->assertJsonPath('data.questions.3.is_correct', true)
            ->assertJsonPath('data.questions.4.is_correct', false);
    }

    /** T27 — a picked option must be one of that question's own options. */
    public function test_an_option_of_another_question_is_rejected(): void
    {
        Passport::actingAs($this->student);

        $this->submit(['choice' => $this->optionId('find_error', 'are')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.answer']);

        $this->assertSame(0, QuizAttempt::count());
    }

    /** T27 — every question must be in this quiz. */
    public function test_a_question_of_another_quiz_is_rejected(): void
    {
        $other = Quiz::factory()->for(Lesson::factory()->create())->create();
        $foreign = Question::factory()->for($other)->create(['type' => QuestionType::FillBlank]);
        Passport::actingAs($this->student);

        $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => [
            ['question_id' => $this->q['fill_blank']->id, 'answer' => 'is not'],
            ['question_id' => $foreign->id, 'answer' => 'is not'],
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.1.question_id']);

        $this->assertSame(0, QuizAttempt::count());
    }

    /** T27 — the answer must have the right shape for its question type. */
    public function test_an_answer_of_the_wrong_type_is_rejected(): void
    {
        Passport::actingAs($this->student);

        $this->submit(['choice' => 'am'])->assertJsonValidationErrors(['answers.0.answer']);
        $this->submit(['fill_blank' => ['is', 'not']])->assertJsonValidationErrors(['answers.0.answer']);
        $this->submit(['word_order' => 'Where are you from?'])->assertJsonValidationErrors(['answers.0.answer']);

        $this->assertSame(0, QuizAttempt::count());
    }

    /** T27 — too many submits in one minute are blocked. */
    public function test_quiz_submit_is_rate_limited(): void
    {
        Passport::actingAs($this->student);
        $limit = config('learning.quiz.submit_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->submit(['choice' => $this->optionId('choice', 'am')])->assertCreated();
        }

        $this->submit(['choice' => $this->optionId('choice', 'am')])->assertTooManyRequests();
        $this->assertSame($limit, QuizAttempt::count());
    }

    public function test_points_decide_the_percent_and_pass_score(): void
    {
        $this->q['choice']->update(['points' => 6]); // 6 of 10 points = 60% < 70%
        Passport::actingAs($this->student);

        $this->submit(['choice' => $this->optionId('choice', 'am')])
            ->assertJsonPath('data.score', 6)
            ->assertJsonPath('data.total', 10)
            ->assertJsonPath('data.percent', 60)
            ->assertJsonPath('data.passed', false);
    }

    public function test_result_shows_correct_answers_and_explanations(): void
    {
        Passport::actingAs($this->student);

        $this->submit(['fill_blank' => 'is'])
            ->assertJsonPath('data.questions.0.correct_option_id', $this->optionId('choice', 'am'))
            ->assertJsonPath('data.questions.3.your_answer', 'is')
            ->assertJsonPath('data.questions.3.correct_answers', ["isn't", 'is not'])
            ->assertJsonPath('data.questions.4.correct_text', 'Where are you from?')
            ->assertJsonPath('data.questions.1.explanation', 'SECRET EXPLANATION true_false')
            ->assertJsonPath('data.questions.1.explanation_km', 'ពន្យល់')
            ->assertJsonPath('data.lesson.slug', 'to-be');
    }

    public function test_draft_lessons_and_empty_quizzes_are_not_found(): void
    {
        Passport::actingAs($this->student);

        $empty = Quiz::factory()->for(Lesson::factory()->create(['slug' => 'empty']))->create();
        $this->getJson('/api/web/student/lessons/empty/quiz')->assertNotFound();
        $this->postJson("/api/web/student/quizzes/{$empty->id}/submit", ['answers' => []])->assertNotFound();

        $this->lesson->update(['status' => 'draft']);
        $this->getJson('/api/web/student/lessons/to-be/quiz')->assertNotFound();
        $this->submit([])->assertNotFound();
    }

    public function test_bad_answers_are_rejected(): void
    {
        Passport::actingAs($this->student);

        $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => [['answer' => 1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['answers.0.question_id']);

        $this->postJson("/api/web/student/quizzes/{$this->quiz->id}/submit", ['answers' => [['question_id' => $this->q['choice']->id, 'answer' => ['x' => ['y']]]]])
            ->assertUnprocessable();
    }
}
