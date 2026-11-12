<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Learning\LearningStats;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponses;

    /** Numbers for the admin dashboard: users, sign-ups, active students, quizzes, popular lessons. */
    public function __invoke(LearningStats $stats): JsonResponse
    {
        return $this->ok($stats->summary());
    }
}
