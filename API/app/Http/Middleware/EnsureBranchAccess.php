<?php

namespace App\Http\Middleware;

use App\Services\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects a request whose X-Branch-Id is a branch the user may not access.
 */
class EnsureBranchAccess
{
    public function __construct(private BranchContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $branchId = $request->header('X-Branch-Id', '*');

        if ($branchId !== '*' && ! $this->context->allows($request->user(), $branchId)) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to this branch.',
            ], 403);
        }

        return $next($request);
    }
}
