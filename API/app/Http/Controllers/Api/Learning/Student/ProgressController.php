<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Learning\ProgressReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "My Progress": lessons done per skill and per level, quiz scores, the scores chart and topics to review. */
class ProgressController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request, ProgressReport $report): JsonResponse
    {
        $student = $request->user();
        $scores = $report->quizScores($student);

        return $this->ok([
            'skills' => $report->bySkill($student),
            'levels' => $report->byLevel($student),
            'quizzes' => $scores,
            'to_review' => $report->topicsToReview($scores),
            'history' => $report->scoreHistory($student),
        ]);
    }
}
