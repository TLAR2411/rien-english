<?php

namespace App\Models\Concerns;

use App\Services\BranchContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * For rows shared by all of one owner's buildings. Every query is limited to
 * the logged-in user's owner, and new rows are given that owner automatically.
 * Console commands have no user and must pass explicit ids themselves.
 */
trait CompanyScoped
{
    protected static function bootCompanyScoped(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            $user = Auth::user();

            if (!$user && app()->runningInConsole()) {
                return;
            }

            $query->where(
                $query->getModel()->getTable() . '.company_id',
                app(BranchContext::class)->companyId($user) ?? 0
            );
        });

        static::creating(function ($model) {
            $model->company_id ??= app(BranchContext::class)->companyId(Auth::user());
        });
    }
}
