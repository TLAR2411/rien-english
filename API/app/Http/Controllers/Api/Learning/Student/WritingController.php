<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Enums\WritingStatus;
use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Student\SubmitWritingRequest;
use App\Http\Resources\Learning\Student\WritingSubmissionResource;
use App\Models\Learning\Lesson;
use App\Models\Learning\WritingSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Writing lessons (logged-in students): send a writing, see its status,
 * and "My Writing" (every writing with the teacher's score + feedback, T34).
 * The lesson's sample answer is only sent after the student's first writing.
 */
class WritingController extends Controller
{
    use ApiResponses;

    /** My Writing: all my writings, newest first. */
    public function index(Request $request): JsonResponse
    {
        $writings = $request->user()->writingSubmissions()
            ->with('lesson.course.level')
            ->latest('id')
            ->paginate(min(50, max(1, $request->integer('limit', 15))));

        return $this->paginated(WritingSubmissionResource::collection($writings));
    }

    /** One of my writings, with the lesson's task and sample answer. Someone else's → 404. */
    public function mine(Request $request, WritingSubmission $writing): JsonResponse
    {
        abort_unless($writing->user_id === $request->user()->id, 404);
        $lesson = $writing->lesson()->with('course.level')->first();

        return $this->ok([
            ...(new WritingSubmissionResource($writing->setRelation('lesson', $lesson)))->resolve(),
            'task' => [
                ...$lesson->only(['checklist', 'min_words', 'max_words', 'sample_answer']),
                // The writing page can link back while the lesson is still published
                'is_open' => $lesson->isVisible(),
            ],
        ]);
    }

    /** My writings for this lesson (newest first) + the sample answer once I sent one. */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless($lesson->acceptsWriting(), 404);

        $submissions = $request->user()->writingSubmissions()
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->get();

        return $this->ok([
            'submissions' => WritingSubmissionResource::collection($submissions),
            'sample_answer' => $submissions->isNotEmpty() ? $lesson->sample_answer : null,
        ]);
    }

    /** Save the writing as "waiting for review" and show the sample answer. */
    public function store(SubmitWritingRequest $request, Lesson $lesson): JsonResponse
    {
        $submission = $request->user()->writingSubmissions()->create([
            'lesson_id' => $lesson->id,
            'text' => trim($request->validated('text')),
            'status' => WritingStatus::Pending,
        ]);

        // No message: the page shows "Waiting for review" itself
        return $this->ok([
            'submission' => new WritingSubmissionResource($submission),
            'sample_answer' => $lesson->sample_answer,
        ], code: 201);
    }
}
