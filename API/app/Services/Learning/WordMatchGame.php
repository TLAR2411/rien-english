<?php

namespace App\Services\Learning;

use App\Enums\LessonSkill;
use App\Models\Learning\Lesson;
use App\Models\Learning\LessonWord;
use App\Models\Learning\WordMatchAttempt;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;

/**
 * Matching game of a vocabulary lesson: match each word to its meaning.
 *
 * The browser gets the words and the meanings in a mixed-up order. A meaning
 * only carries an encrypted `token` (the browser cannot read which word it
 * belongs to), so the answers are checked here, on the server.
 * Limits: config/learning.php → matching.
 */
class WordMatchGame
{
    /** Words that can be played: they have a meaning (Khmer or English). */
    public function words(Lesson $lesson): Collection
    {
        return $lesson->words()
            ->where(fn ($q) => $q->whereNotNull('meaning_km')->orWhereNotNull('meaning_en'))
            ->get();
    }

    /** A published vocabulary lesson with enough words. */
    public function isOpen(Lesson $lesson): bool
    {
        return $lesson->skill === LessonSkill::Vocabulary
            && $lesson->isVisible()
            && $this->words($lesson)->count() >= config('learning.matching.min_words');
    }

    /** How many pairs one game has. */
    public function size(Lesson $lesson): int
    {
        return min($this->words($lesson)->count(), config('learning.matching.max_pairs'));
    }

    /** A new game: some words (random when there are many) and their meanings, mixed up. */
    public function newGame(Lesson $lesson): array
    {
        $words = $this->words($lesson)->shuffle()->take($this->size($lesson))->values();

        return [
            'words' => $words->map(fn (LessonWord $word) => [
                'id' => $word->id,
                'word' => $word->word,
                'audio_url' => $word->audio_url,
            ])->all(),
            'meanings' => $words->shuffle()->map(fn (LessonWord $word) => [
                'token' => $this->token($lesson, $word),
                ...$this->meaning($word),
            ])->values()->all(),
        ];
    }

    /** The word id inside a meaning token, or null when it is not a token of this lesson. */
    public function wordIdFromToken(Lesson $lesson, string $token): ?int
    {
        try {
            [$lessonId, $wordId] = explode(':', Crypt::decryptString($token)) + [null, null];
        } catch (DecryptException) {
            return null;
        }

        return (int) $lessonId === $lesson->id ? (int) $wordId : null;
    }

    /**
     * Check the pairs (word id => the word id behind the chosen meaning), save the game.
     *
     * @param  array<int, int>  $pairs
     */
    public function grade(User $student, Lesson $lesson, array $pairs): array
    {
        $words = $this->words($lesson)->keyBy('id');
        $results = collect($pairs)->map(fn (int $chosenId, int $wordId) => [
            'word_id' => $wordId,
            'word' => $words[$wordId]->word,
            'correct' => $chosenId === $wordId,
            'your_meaning' => $this->meaning($words[$chosenId]),
            'right_meaning' => $this->meaning($words[$wordId]),
        ])->values();

        $score = $results->where('correct', true)->count();
        $total = $results->count();

        $attempt = WordMatchAttempt::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'score' => $score,
            'total' => $total,
            'percent' => (int) round($score / max(1, $total) * 100),
        ]);

        return [
            'score' => $score,
            'total' => $total,
            'percent' => $attempt->percent,
            'best_percent' => $this->bestPercent($student, $lesson),
            'pairs' => $results->all(),
        ];
    }

    public function bestPercent(User $student, Lesson $lesson): ?int
    {
        return WordMatchAttempt::where('user_id', $student->id)->where('lesson_id', $lesson->id)->max('percent');
    }

    private function token(Lesson $lesson, LessonWord $word): string
    {
        return Crypt::encryptString("{$lesson->id}:{$word->id}");
    }

    /** What a meaning card shows: Khmer when there is one, with the simple English meaning. */
    private function meaning(LessonWord $word): array
    {
        return ['text' => $word->meaning_km ?: $word->meaning_en, 'text_en' => $word->meaning_km ? $word->meaning_en : null];
    }
}
