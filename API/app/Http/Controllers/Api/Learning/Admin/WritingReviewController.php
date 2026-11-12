<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Enums\WritingStatus;
use App\Http\Requests\Learning\ReviewWritingRequest;
use App\Http\Resources\Learning\WritingReviewResource;
use App\Models\Learning\WritingSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Writing Review (admin + teacher): students' writings, give a score and feedback.
 * Waiting writings are listed oldest first (first sent, first reviewed).
 */
class WritingReviewController extends AdminController
{
    private const WITH = ['user', 'reviewer', 'lesson.course.level'];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:view-writing', only: ['index', 'show']),
            new Middleware('permission:review-writing', only: ['review']),
        ];
    }

    /** Filters: status (pending / reviewed), lesson_id, search (student name / email, lesson title). */
    public function index(Request $request): JsonResponse
    {
        $status = $this->filter($request, 'status');

        $writings = WritingSubmission::query()
            ->with(self::WITH)
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($this->filter($request, 'lesson_id'), fn (Builder $q, $lessonId) => $q->where('lesson_id', $lessonId))
            ->when($this->filter($request, 'search'), fn (Builder $q, $search) => $q->where(fn (Builder $q) => $q
                ->whereHas('user', fn (Builder $u) => $u->where(fn (Builder $u) => $u
                    ->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_kh', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
                ->orWhereHas('lesson', fn (Builder $l) => $l->where('title_en', 'like', "%{$search}%"))))
            ->when(
                $status === WritingStatus::Pending->value,
                fn (Builder $q) => $q->oldest('id'),
                fn (Builder $q) => $q->orderByRaw('status = ? desc', [WritingStatus::Pending->value])->latest('id'),
            )
            ->paginate($this->perPage($request));

        return $this->paginated(WritingReviewResource::collection($writings));
    }

    /** One writing with the lesson's task, the student's earlier writings for it, and the next one waiting. */
    public function show(WritingSubmission $writing): JsonResponse
    {
        $writing->load(self::WITH);

        return $this->ok([
            ...(new WritingReviewResource($writing))->withTask()->resolve(),
            'earlier' => WritingReviewResource::collection(
                WritingSubmission::with('reviewer')
                    ->where('user_id', $writing->user_id)
                    ->where('lesson_id', $writing->lesson_id)
                    ->whereKeyNot($writing->id)
                    ->latest('id')
                    ->get()
            )->resolve(),
            'next_pending_id' => $this->nextPending($writing),
            'pending_count' => WritingSubmission::where('status', WritingStatus::Pending)->count(),
        ]);
    }

    /** Save the score + feedback. A reviewed writing can be changed again. */
    public function review(ReviewWritingRequest $request, WritingSubmission $writing): JsonResponse
    {
        $writing->update([
            ...$request->validated(),
            'status' => WritingStatus::Reviewed,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return $this->ok([
            ...(new WritingReviewResource($writing->load(self::WITH)))->withTask()->resolve(),
            'next_pending_id' => $this->nextPending($writing),
        ], 'Saved.');
    }

    /** The oldest other writing still waiting. */
    private function nextPending(WritingSubmission $writing): ?int
    {
        return WritingSubmission::where('status', WritingStatus::Pending)
            ->whereKeyNot($writing->id)
            ->oldest('id')
            ->value('id');
    }
}
