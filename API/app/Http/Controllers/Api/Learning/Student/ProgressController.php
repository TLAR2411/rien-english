<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Learning\ProgressReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** "My Progress": lessons done per skill and per level, and the student's quiz scores. */
class ProgressController extends Controller
{
    use ApiResponses;

    public function __invoke(Request $request, ProgressReport $report): JsonResponse
    {
        $student = $request->user();

        return $this->ok([
            'skills' => $report->bySkill($student),
            'levels' => $report->byLevel($student),
            'quizzes' => $report->quizScores($student),
        ]);
    }
}
