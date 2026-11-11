<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The JSON shape the Vue app expects: { status, data, message }.
 * Errors (404, 422, 403 …) use Laravel's normal error responses.
 */
trait ApiResponses
{
    protected function ok(mixed $data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json(array_filter([
            'status' => true,
            'data' => $data,
            'message' => $message,
        ], fn ($value) => $value !== null), $code);
    }

    protected function created(mixed $data = null, string $message = 'Saved.'): JsonResponse
    {
        return $this->ok($data, $message, 201);
    }

    /** A paginated resource list in the shape AppCardTable reads: data.data / data.meta / data.links. */
    protected function paginated(ResourceCollection $collection): JsonResponse
    {
        return $this->ok($collection->response()->getData(true));
    }
}
