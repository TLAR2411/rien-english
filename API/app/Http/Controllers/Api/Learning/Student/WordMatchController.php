<?php

namespace App\Http\Controllers\Api\Learning\Student;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\Student\MatchWordsRequest;
use App\Models\Learning\Lesson;
use App\Services\Learning\WordMatchGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Matching game of a vocabulary lesson (logged-in students). Graded on the server. */
class WordMatchController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly WordMatchGame $game) {}

    /** A new game (mixed-up words and meanings, no answers) + my best score. */
    public function show(Request $request, Lesson $lesson): JsonResponse
    {
        abort_unless($this->game->isOpen($lesson), 404);

        return $this->ok([
            ...$this->game->newGame($lesson),
            'best_percent' => $this->game->bestPercent($request->user(), $lesson),
        ]);
    }

    /** Check the pairs, save the game, return each pair's right meaning. */
    public function check(MatchWordsRequest $request, Lesson $lesson): JsonResponse
    {
        return $this->ok($this->game->grade($request->user(), $lesson, $request->pairs()), code: 201);
    }
}
