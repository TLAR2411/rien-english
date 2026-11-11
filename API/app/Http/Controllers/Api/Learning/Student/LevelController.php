<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Student\LevelRequest;
use Illuminate\Http\JsonResponse;

/** Save the level the student chose in the top bar. Their lesson list and dashboard follow it. */
class LevelController extends Controller
{
    use ApiResponses;

    public function update(LevelRequest $request): JsonResponse
    {
        $student = $request->user();
        $student->forceFill(['level_id' => $request->validated('level_id')])->saveQuietly();

        return $this->ok($student->level()->first(['id', 'code', 'name', 'name_km']));
    }
}
