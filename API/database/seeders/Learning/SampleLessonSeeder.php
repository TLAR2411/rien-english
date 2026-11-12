<?php

namespace Database\Seeders\Learning;

use App\Enums\QuestionType;
use App\Models\Learning\Course;
use App\Models\Learning\Lesson;
use App\Models\Learning\Quiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Sample lessons from the files in ./sample-lessons (one PHP file per lesson,
 * returning an array). To add a lesson, copy a file and change the text.
 *
 * Content must be reviewed by an English teacher before launch.
 * The lessons are created as drafts, so students only see them after a
 * teacher has checked them and set them to Published.
 *
 * Safe to run again: lessons are matched by slug and their examples / quiz
 * are replaced — except a quiz students already took, which is left alone.
 *
 * File format (see a1-1-to-be.php; a reading lesson: a1-5-my-family.php):
 *   course       course slug, e.g. "english-for-beginners" (from LevelSeeder)
 *   lesson       lesson columns (skill, title_en, title_km, slug, summary, content, content_km …); skill defaults to grammar
 *   examples     [[sentence, Khmer translation], …]
 *   words        optional new words: [[word, English meaning, Khmer meaning, example sentence], …]
 *   audio        optional audio file in ./sample-media (listening), copied to the uploads disk
 *   quiz         ['title' => …, 'questions' => [question, …]]
 *   question     type, prompt, explanation, explanation_km, and its answer:
 *                  multiple_choice / find_error → 'options' => ['text' => is correct?]
 *                  true_false                   → 'correct' => true | false
 *                  fill_blank / word_order      → 'answers' => ['accepted answer', …]
 */
class SampleLessonSeeder extends Seeder
{
    public function run(): void
    {
        foreach (glob(__DIR__.'/sample-lessons/*.php') as $file) {
            DB::transaction(fn () => $this->seedLesson(require $file));
        }
    }

    private function seedLesson(array $data): void
    {
        $course = Course::where('slug', $data['course'])->first()
            ?? throw new RuntimeException("Course [{$data['course']}] not found. Run LevelSeeder first.");

        $lesson = Lesson::updateOrCreate(
            ['slug' => $data['lesson']['slug']],
            [
                'status' => 'draft', 'skill' => 'grammar', ...$data['lesson'], 'course_id' => $course->id,
                ...(isset($data['audio']) ? ['audio_url' => $this->copyAudio($data['audio'])] : []),
            ],
        );

        $lesson->examples()->delete();
        foreach ($data['examples'] ?? [] as [$sentence, $translation]) {
            $lesson->examples()->create(['sentence' => $sentence, 'translation_km' => $translation]);
        }

        $lesson->words()->delete();
        foreach ($data['words'] ?? [] as [$word, $meaning, $meaningKm, $example]) {
            $lesson->words()->create(['word' => $word, 'meaning_en' => $meaning, 'meaning_km' => $meaningKm, 'example' => $example]);
        }

        $this->seedQuiz($lesson, $data['quiz']);
    }

    private function seedQuiz(Lesson $lesson, array $data): void
    {
        $quiz = Quiz::firstOrNew(['lesson_id' => $lesson->id]);
        if ($quiz->exists && $quiz->attempts()->exists()) {
            return; // students' results depend on these questions
        }

        $quiz->fill(['title' => $data['title'], 'pass_score' => $data['pass_score'] ?? config('learning.quiz.pass_score')])->save();
        $quiz->questions()->delete();

        foreach ($data['questions'] as $item) {
            $question = $quiz->questions()->create([
                'type' => $item['type'],
                'prompt' => $item['prompt'],
                'explanation' => $item['explanation'] ?? null,
                'explanation_km' => $item['explanation_km'] ?? null,
                'points' => $item['points'] ?? 1,
            ]);

            foreach ($this->optionsFor($item) as $position => [$text, $isCorrect]) {
                $question->options()->create(['text' => $text, 'is_correct' => $isCorrect, 'sort_order' => $position + 1]);
            }
        }
    }

    /** Puts a sample audio file on the uploads disk → its short path (saved in audio_url). */
    private function copyAudio(string $file): string
    {
        $source = __DIR__.'/sample-media/'.$file;
        is_file($source) || throw new RuntimeException("Sample audio [{$file}] not found in sample-media.");

        $path = config('learning.uploads.folder').'/audio/samples/'.$file;
        Storage::disk(config('learning.uploads.disk'))->put($path, file_get_contents($source));

        return $path;
    }

    /** @return list<array{0: string, 1: bool}> */
    private function optionsFor(array $item): array
    {
        return match (QuestionType::from($item['type'])) {
            QuestionType::TrueFalse => [['True', $item['correct']], ['False', ! $item['correct']]],
            QuestionType::FillBlank, QuestionType::WordOrder => array_map(fn ($answer) => [$answer, true], $item['answers']),
            default => array_map(null, array_keys($item['options']), array_values($item['options'])),
        };
    }
}
