<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardController extends Controller
{
    /**
     * Everything the admin dashboard needs in one call, scoped to the selected branch.
     */
    public function index(Request $request)
    {
        try {
            $now = Carbon::now('Asia/Phnom_Penh');
            $branchId = $this->getBranch();

            // All users of the selected branch except the system admin (id 1)
            $users = fn() => User::query()
                ->whereBranch($branchId)
                ->where('users.id', '!=', 1);

            $active = fn() => $users()->where('users.is_active', true);

            $summary = [
                'total_users' => $users()->count(),
                'active_users' => $active()->count(),
                'inactive_users' => $users()->where('users.is_active', false)->count(),
                'new_this_month' => $users()->where('users.created_at', '>=', $now->copy()->startOfMonth())->count(),
                'logged_in_today' => $active()->where('users.last_login', '>=', $now->copy()->startOfDay())->count(),
                'never_logged_in' => $active()->whereNull('users.last_login')->count(),
                'idle_30_days' => $active()->where('users.last_login', '<', $now->copy()->subDays(30))->count(),
                'sub_accounts' => $active()->where('users.type', 'sub')->count(),
                'branches' => Branch::where('is_active', true)->count(),
            ];

            $byBranch = $active()
                ->join('branches as b', 'users.branch_id', 'b.id')
                ->groupBy('b.id', 'b.name_kh', 'b.name_en', 'b.abbr')
                ->orderByDesc('total')
                ->get([
                    'b.id',
                    'b.name_kh',
                    'b.name_en',
                    'b.abbr',
                    DB::raw('COUNT(*) as total'),
                ]);

            $byPosition = $active()
                ->join('positions as p', 'users.position_id', 'p.id')
                ->groupBy('p.id', 'p.name_kh', 'p.name_en', 'p.abbr', 'p.level')
                ->orderByDesc('p.level')
                ->get([
                    'p.id',
                    'p.name_kh',
                    'p.name_en',
                    'p.abbr',
                    'p.level',
                    DB::raw('COUNT(*) as total'),
                ]);

            $byRole = $active()
                ->join('roles as r', 'users.role_id', 'r.id')
                ->groupBy('r.id', 'r.display_name', 'r.abbr')
                ->orderByDesc('total')
                ->get([
                    'r.id',
                    'r.display_name',
                    'r.abbr',
                    DB::raw('COUNT(*) as total'),
                ]);

            $userColumns = [
                'users.id',
                'users.name_kh',
                'users.name_en',
                'users.image_path',
                'users.last_login',
                'p.name_kh as position_name_kh',
                'p.name_en as position_name_en',
                'b.abbr as branch_abbr',
            ];

            $withPositionBranch = fn($q) => $q
                ->leftJoin('positions as p', 'users.position_id', 'p.id')
                ->leftJoin('branches as b', 'users.branch_id', 'b.id');

            $recentLogins = $withPositionBranch($active())
                ->whereNotNull('users.last_login')
                ->orderByDesc('users.last_login')
                ->limit(5)
                ->get($userColumns);

            // Active accounts nobody is using: never logged in, or idle 30+ days
            $idleUsers = $withPositionBranch($active())
                ->where(function ($q) use ($now) {
                    $q->whereNull('users.last_login')
                        ->orWhere('users.last_login', '<', $now->copy()->subDays(30));
                })
                ->orderByRaw('users.last_login IS NOT NULL, users.last_login')
                ->limit(5)
                ->get($userColumns);

            // Recent activity only for admins allowed to see the activity log
            $recentActivity = [];
            if (Auth::user()?->isAbleTo('view-activity-log')) {
                $recentActivity = Activity::query()
                    ->with('causer:id,name_kh,name_en,image_path')
                    ->when($branchId != '*', function ($q) use ($users) {
                        $q->whereIn('causer_id', $users()->select('users.id'));
                    })
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id', 'log_name', 'description', 'event', 'causer_id', 'causer_type', 'created_at']);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'summary' => $summary,
                    'by_branch' => $byBranch,
                    'by_position' => $byPosition,
                    'by_role' => $byRole,
                    'recent_logins' => $recentLogins,
                    'idle_users' => $idleUsers,
                    'recent_activity' => $recentActivity,
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
