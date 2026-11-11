<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponses;

    /** Numbers for the admin dashboard cards. */
    public function __invoke(): JsonResponse
    {
        return $this->ok([
            'students' => User::whereHasRole(config('roles.student'))->count(),
            'teachers' => User::whereHasRole('teacher')->count(),
        ]);
    }
}
