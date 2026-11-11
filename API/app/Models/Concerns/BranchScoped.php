<?php

namespace App\Models\Concerns;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * For rows that belong to one building. Every query is limited to the
 * buildings the logged-in user may access, so a plain findOrFail is safe.
 * Console commands have no user and must pass explicit ids themselves.
 */
trait BranchScoped
{
    use ScopesBranch;

    protected static function bootBranchScoped(): void
    {
        static::addGlobalScope('branch', function (Builder $query) {
            $user = Auth::user();

            if (!$user && app()->runningInConsole()) {
                return;
            }

            $query->whereIn(
                $query->getModel()->branchColumn(),
                app(BranchContext::class)->allowedBranchIds($user)->all()
            );
        });
    }

    protected function branchColumn(): string
    {
        return $this->getTable() . '.branch_id';
    }
}
