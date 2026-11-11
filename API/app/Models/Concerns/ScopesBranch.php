<?php

namespace App\Models\Concerns;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Limits a query to the branches the logged-in user may access. Returns no
 * rows when there is no logged-in user, so a missed check can never leak data.
 */
trait ScopesBranch
{
    #[Scope]
    public function whereBranch($query, string $branchId)
    {
        $allowed = app(BranchContext::class)->allowedBranchIds(Auth::user());

        if ($branchId !== '*') {
            $allowed = $allowed->intersect([(int) $branchId]);
        }

        $query->whereIn($this->branchColumn(), $allowed->values()->all());
    }
}
