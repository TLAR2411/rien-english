<?php

namespace Tests\Feature\Learning\Database;

use App\Enums\WritingStatus;
use App\Models\Learning\Lesson;
use App\Models\Learning\Question;
use App\Models\Learning\QuizAttempt;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_activity_is_linked_to_the_user(): void
    {
        $student = $this->userWithRole('student');
        $question = Question::factory()->create();
        $lesson = $question->quiz->lesson;

        $attempt = $student->quizAttempts()->create(['quiz_id' => $question->quiz_id, 'score' => 1, 'total' => 1, 'percent' => 100, 'passed' => true]);
        $attempt->answers()->create(['question_id' => $question->id, 'answer' => ['option_id' => 5], 'is_correct' => true]);
        $student->lessonProgress()->create(['lesson_id' => $lesson->id, 'completed_at' => now()]);
        $writing = $student->writingSubmissions()->create(['lesson_id' => $lesson->id, 'text' => 'I am a student.']);
        $student->enrollments()->create(['course_id' => $lesson->course_id]);

        $this->assertSame(['option_id' => 5], QuizAttempt::first()->answers->first()->answer);
        $this->assertSame(WritingStatus::Pending, $writing->fresh()->status);
        $this->assertSame(1, $student->lessonProgress()->count());
        $this->assertSame(1, $student->enrollments()->count());
    }

    public function test_a_lesson_is_completed_only_once_per_student(): void
    {
        $student = $this->userWithRole('student');
        $lesson = Lesson::factory()->create();
        $student->lessonProgress()->create(['lesson_id' => $lesson->id]);

        $this->expectException(QueryException::class);
        $student->lessonProgress()->create(['lesson_id' => $lesson->id]);
    }
}
