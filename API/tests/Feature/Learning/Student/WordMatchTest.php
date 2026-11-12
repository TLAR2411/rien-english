<?php

namespace Tests\Feature\Learning\Student;

use App\Models\Learning\Lesson;
use App\Models\Learning\LessonWord;
use App\Models\Learning\WordMatchAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** T35 — vocabulary lessons: flashcards for everyone, a matching game graded on the server. */
class WordMatchTest extends TestCase
{
    use RefreshDatabase;

    private Lesson $lesson;

    private User $student;

    /** @var Collection<int, LessonWord> */
    private Collection $words;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->student = $this->userWithRole('student');

        $this->lesson = Lesson::factory()->create(['skill' => 'vocabulary', 'slug' => 'family-words']);
        $this->words = collect([
            ['mother', 'ម្តាយ'], ['father', 'ឪពុក'], ['sister', 'បងស្រី'], ['uncle', 'ពូ'],
        ])->map(fn ($w) => LessonWord::factory()->for($this->lesson)->create([
            'word' => $w[0], 'meaning_km' => $w[1], 'meaning_en' => "meaning of {$w[0]}", 'example' => "This is my {$w[0]}.",
        ]));
    }

    private function game(): array
    {
        return $this->getJson('/api/web/student/lessons/family-words/matching')->assertOk()->json('data');
    }

    /** Pairs from a game: the right meaning for every word, except words in $swap (swapped with each other). */
    private function pairs(array $game, array $swap = []): array
    {
        $tokenOf = collect($game['meanings'])->mapWithKeys(fn ($m) => [$this->words->firstWhere('meaning_km', $m['text'])->id => $m['token']]);
        $ids = collect($game['words'])->pluck('id')->all();
        $chosen = array_combine($ids, $ids);
        if ($swap) {
            [$a, $b] = $swap;
            [$chosen[$a], $chosen[$b]] = [$chosen[$b], $chosen[$a]];
        }

        return collect($chosen)->map(fn ($meaningOf, $wordId) => ['word_id' => $wordId, 'token' => $tokenOf[$meaningOf]])->values()->all();
    }

    private function check(array $pairs): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/web/student/lessons/family-words/matching', ['pairs' => $pairs]);
    }

    public function test_visitor_gets_the_flashcards_without_login(): void
    {
        $this->getJson('/api/web/site/lessons/family-words')
            ->assertOk()
            ->assertJsonCount(4, 'data.words')
            ->assertJsonPath('data.words.0.word', 'mother')
            ->assertJsonPath('data.words.0.meaning_km', 'ម្តាយ')
            ->assertJsonPath('data.words.0.example', 'This is my mother.');
    }

    public function test_the_game_needs_login(): void
    {
        $this->getJson('/api/web/student/lessons/family-words/matching')->assertUnauthorized();
        $this->check([])->assertUnauthorized();
    }

    public function test_the_game_never_tells_which_meaning_belongs_to_which_word(): void
    {
        Passport::actingAs($this->student);
        $game = $this->game();

        $this->assertCount(4, $game['words']);
        $this->assertCount(4, $game['meanings']);
        foreach ($game['meanings'] as $meaning) {
            $this->assertSame(['token', 'text', 'text_en'], array_keys($meaning));
        }
        $this->assertSame(['id', 'word', 'audio_url'], array_keys($game['words'][0]));
    }

    public function test_all_right_scores_100_and_is_saved(): void
    {
        Passport::actingAs($this->student);

        $this->check($this->pairs($this->game()))
            ->assertCreated()
            ->assertJsonPath('data.score', 4)
            ->assertJsonPath('data.total', 4)
            ->assertJsonPath('data.percent', 100)
            ->assertJsonPath('data.best_percent', 100);

        $this->assertDatabaseHas('word_match_attempts', ['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id, 'percent' => 100]);
    }

    public function test_wrong_pairs_are_marked_with_the_right_meaning(): void
    {
        Passport::actingAs($this->student);
        WordMatchAttempt::create(['user_id' => $this->student->id, 'lesson_id' => $this->lesson->id, 'score' => 4, 'total' => 4, 'percent' => 100]);

        $game = $this->game();
        $this->assertSame(100, $game['best_percent']);
        [$a, $b] = array_column($game['words'], 'id');

        $result = $this->check($this->pairs($game, [$a, $b]))
            ->assertCreated()
            ->assertJsonPath('data.score', 2)
            ->assertJsonPath('data.percent', 50)
            ->assertJsonPath('data.best_percent', 100)
            ->json('data.pairs');

        $wrong = collect($result)->firstWhere('word_id', $a);
        $this->assertFalse($wrong['correct']);
        $this->assertSame($this->words->firstWhere('id', $a)->meaning_km, $wrong['right_meaning']['text']);
        $this->assertSame($this->words->firstWhere('id', $b)->meaning_km, $wrong['your_meaning']['text']);
    }

    public function test_every_word_must_be_matched_once_with_a_meaning_of_this_lesson(): void
    {
        Passport::actingAs($this->student);
        $pairs = $this->pairs($this->game());

        // Not every word
        $this->check(array_slice($pairs, 0, 3))->assertUnprocessable()->assertJsonValidationErrors('pairs');

        // A made-up token, and a meaning of another lesson
        $fake = $pairs;
        $fake[0]['token'] = 'not-a-token';
        $this->check($fake)->assertUnprocessable()->assertJsonValidationErrors('pairs.0.token');

        $other = LessonWord::factory()->create();
        $fake[0]['token'] = Crypt::encryptString("{$other->lesson_id}:{$other->id}");
        $this->check($fake)->assertUnprocessable()->assertJsonValidationErrors('pairs.0.token');

        // The same meaning twice (two tokens of one word from two games)
        $again = $this->pairs($this->game());
        $twice = $pairs;
        $twice[1]['token'] = collect($again)->firstWhere('word_id', $pairs[0]['word_id'])['token'];
        $this->check($twice)->assertUnprocessable()->assertJsonValidationErrors('pairs');

        $this->assertSame(0, WordMatchAttempt::count());
    }

    public function test_a_game_has_at_most_eight_pairs(): void
    {
        LessonWord::factory()->for($this->lesson)->count(8)->create(['meaning_km' => null]);
        Passport::actingAs($this->student);

        $game = $this->game();
        $this->assertCount(8, $game['words']);
        $this->assertCount(8, $game['meanings']);
    }

    public function test_no_game_for_drafts_other_skills_or_too_few_words(): void
    {
        Passport::actingAs($this->student);

        $this->lesson->update(['status' => 'draft']);
        $this->getJson('/api/web/student/lessons/family-words/matching')->assertNotFound();

        $this->lesson->update(['status' => 'published', 'skill' => 'reading']);
        $this->getJson('/api/web/student/lessons/family-words/matching')->assertNotFound();

        $this->lesson->update(['skill' => 'vocabulary']);
        $this->lesson->words()->limit(2)->delete();
        $this->getJson('/api/web/student/lessons/family-words/matching')->assertNotFound();
    }
}
