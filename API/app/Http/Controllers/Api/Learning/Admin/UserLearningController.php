<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\Learning\Student\QuizAttemptResource;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\Learning\ProgressReport;
use App\Services\Learning\StudentProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One user's learning, for the admin user page: who they are, numbers,
 * lessons done per skill, scores per quiz, and every quiz try.
 * Hidden (draft) lessons are included: the admin sees everything the user did.
 */
class UserLearningController extends Controller
{
    use ApiResponses;

    public function __construct(private BranchContext $branches) {}

    public function show(Request $request, User $user, ProgressReport $report, StudentProgress $progress): JsonResponse
    {
        $this->authorizeBranch($request, $user);

        return $this->ok([
            'user' => [
                ...$user->only(['id', 'name_en', 'name_kh', 'username', 'email', 'is_active', 'last_login', 'created_at']),
                'role' => $user->role?->only(['id', 'name', 'display_name']),
                'level' => $user->level?->only(['id', 'code', 'name', 'name_km']),
            ],
            'stats' => [
                'lessons_done' => $progress->doneLessons($user)->count(),
                'quizzes_taken' => $user->quizAttempts()->count(),
                'quizzes_passed' => $user->quizAttempts()->where('passed', true)->distinct()->count('quiz_id'),
                'average_percent' => $this->roundOrNull($user->quizAttempts()->avg('percent')),
            ],
            'skills' => $report->bySkill($user),
            'quizzes' => $report->quizScores($user, visibleOnly: false),
        ]);
    }

    /** Every quiz try of the user, newest first (paginated for the table). */
    public function attempts(Request $request, User $user): JsonResponse
    {
        $this->authorizeBranch($request, $user);

        $attempts = $user->quizAttempts()
            ->with('quiz:id,lesson_id,title', 'quiz.lesson:id,slug,title_en,title_km')
            ->latest('finished_at')
            ->latest('id')
            ->paginate($request->integer('limit') ?: config('learning.per_page'));

        return $this->paginated(QuizAttemptResource::collection($attempts));
    }

    /** Admins only see users of the branches they may open (same rule as the users list). */
    private function authorizeBranch(Request $request, User $user): void
    {
        abort_unless($this->branches->allows($request->user(), $user->branch_id), 404);
    }

    private function roundOrNull(mixed $value): ?int
    {
        return $value === null ? null : (int) round($value);
    }
}
