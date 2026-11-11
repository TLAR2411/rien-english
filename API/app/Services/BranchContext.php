<?php

namespace App\Services;

use App\Models\Auth\UserBranch;
use App\Models\Core\Branch;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The one place that decides which branches (buildings) a user may access.
 * Access never reaches outside the user's own company (owner).
 *
 * users.manage_branch: 1 = own branch, 2 = listed branches,
 * 3 = all company branches, 4 = all company branches except the listed ones.
 */
class BranchContext
{
    /** @var array<int, Collection<int, int>> */
    private array $allowed = [];

    /**
     * @return Collection<int, int> active branch ids, lowest first
     */
    public function allowedBranchIds(?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        return $this->allowed[$user->id] ??= $this->resolve($user);
    }

    public function companyId(?User $user): ?int
    {
        return $user ? Branch::whereKey($user->branch_id)->value('company_id') : null;
    }

    public function allows(?User $user, int|string|null $branchId): bool
    {
        return is_numeric($branchId)
            && $this->allowedBranchIds($user)->contains((int) $branchId);
    }

    private function resolve(User $user): Collection
    {
        $companyId = $this->companyId($user);
        if (!$companyId) {
            return collect();
        }

        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true);

        $listed = fn() => UserBranch::where('user_id', $user->id)->pluck('branch_id');

        match ((int) $user->manage_branch) {
            1 => $branches->whereKey($user->branch_id),
            // The home branch stays included: login selects it by default
            2 => $branches->whereKey($listed()->push($user->branch_id)->unique()->all()),
            3 => null,
            4 => $branches->whereKeyNot($listed()->all()),
            default => $branches->whereRaw('1 = 0'),
        };

        return $branches->orderBy('id')->pluck('id')->map(fn($id) => (int) $id)->values();
    }
}
