<?php

namespace App\Http\Controllers\Api\Learning\Site;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Learning\Level;
use Illuminate\Http\JsonResponse;

/** The levels (A1–C1), for the level filter of the public lesson list. */
class LevelController extends Controller
{
    use ApiResponses;

    public function __invoke(): JsonResponse
    {
        return $this->ok(Level::ordered()->get(['id', 'code', 'name', 'name_km']));
    }
}
