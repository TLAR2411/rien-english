<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UserPasswordRequest;
use App\Http\Resources\DataTableResource;
use App\Models\Address\Commune;
use App\Models\Address\District;
use App\Models\Address\Province;
use App\Models\Address\Village;
use App\Models\Auth\PermissionUser;
use App\Models\Auth\Position;
use App\Models\Auth\Role;
use App\Models\Auth\UserBranch;
use App\Models\Core\Branch;
use App\Models\Core\Setting;
use App\Models\Hr\Employee;
use App\Models\Hr\UserAccessRequest;
use App\Models\User;
use App\Services\BranchContext;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{

    private $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function all(Request $request)
    {
        $users = User::query()
            ->leftJoin('roles as r', 'users.role_id', 'r.id')
            ->leftJoin('positions as p', 'users.position_id', 'p.id')
            ->select([
                'users.id',
                'users.name_kh',
                'users.name_en',
                'users.gender',
                'r.display_name',
                'r.abbr as role_abbr',
                'p.name_kh as position_name',
                'p.abbr as position_abbr',
                'p.level as position_level',
            ])
            ->whereBranch($this->getBranch())
            ->where('users.is_active', true)
            ->orderByDesc('p.level')
            ->get();
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    public function allUnderUsers(Request $request)
    {
        $users = User::query()
            ->leftJoin('roles as r', 'users.role_id', 'r.id')
            ->leftJoin('positions as p', 'users.position_id', 'p.id')
            ->leftJoin('branches as b', 'users.branch_id', 'b.id')
            ->select([
                'users.id',
                'users.name_kh',
                'users.name_en',
                'users.gender',
                'users.image_path',
                'users.contact',
                'r.display_name',
                'r.abbr as role_abbr',
                'p.name_kh as position_name',
                'p.abbr as position_abbr',
                'p.level as position_level',
            ])
            ->where('p.level', ">=", 20)
            ->where('users.is_active', true)
            ->where('users.id', '!=', 1)
            ->where('b.company_id', app(BranchContext::class)->companyId($request->user()))
            ->when($request->choose_branch_id != '*', function ($q) use ($request) {
                return $q->where(function ($q) use ($request) {
                    $q->where('b.id', $request->choose_branch_id)->orWhere('b.is_head', true);
                });
            })
            ->orderByDesc('p.level')
            ->get();
        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    public function show(Request $request)
    {
        try {
            $users = User::with([
                'userBranch',
            ])->findOrFail($request->id);

            $userPermssion = PermissionUser::where("user_id", $request->id)
                ->select([
                    'permission_id as id',
                ])
                ->get();

            $village = Village::where('code', $users->village_code ?? null)->first();
            $commune = Commune::where('code', $village->commune_code ?? null)->first();
            $district = District::where('code', $commune->district_code ?? null)->first();
            $province = Province::where('code', $district->province_code ?? null)->first();

            $users['village_code'] = $village['code'] ?? null;
            $users['commune_code'] = $commune['code'] ?? null;
            $users['district_code'] = $district['code'] ?? null;
            $users['province_code'] = $province['code'] ?? null;

            $users['user_permission'] = $userPermssion;

            return response()->json([
                'status' => true,
                'data' => $users
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $users = User::query()
                ->with([
                    'branch',
                    'role',
                    'position',
                    'underUser'
                ])
                ->whereBranch($this->getBranch())
                ->filter($request->input('filter', []))
                ->when(!Auth::user()->is_super, function ($query) {
                    return $query->where('username', '!=', 'admin');
                })
                ->orderByDesc("id")
                ->getPaginate($request->limit);
            $users = DataTableResource::collection($users)->response()->getData(true);
            return response()->json([
                'status' => true,
                'data' => $users
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Blocks changes that would give someone more access than the caller has.
     */
    private function guardAccessGrant(Request $request, ?int $branchId, ?int $manageBranch): void
    {
        $caller = $request->user();

        abort_unless(app(BranchContext::class)->allows($caller, $branchId), 403, 'You do not have access to this branch.');

        // 3 and 4 reach every branch of the company
        abort_if(
            in_array($manageBranch, [3, 4]) && !$caller->is_super && $caller->manage_branch != 3,
            403,
            'You cannot give this branch access.'
        );
    }

    /**
     * Only a super user may change a super user's login or permissions.
     */
    private function guardSuperTarget(Request $request): void
    {
        $target = User::find($request->id);

        abort_if($target?->is_super && !$request->user()->is_super, 403, 'You cannot change a super user.');
    }

    public function store(StoreUserRequest $request)
    {
        // An account made from an employee's request stays in the employee's branch
        $branchId = $request->employee_id
            ? Employee::findOrFail($request->employee_id)->branch_id
            : $this->currentBranchId();

        $this->guardAccessGrant($request, $branchId, $request->integer('manage_branch'));

        $userData = [];
        DB::beginTransaction();
        try {

            $companyName = Cache::remember('setting_company_name', 86400, function () {
                return Setting::where('key', 'company_email')->value('value');
            });

            $defaultPassword = Cache::remember('setting_default_password', 86400, function () {
                return Setting::where('key', 'default_password')->value('value');
            });


            $lowerName = Str::lower($request->name_en);
            $name = explode(" ", $lowerName);

            $userData = $request->except(['is_super', 'type', 'parent_user_id', 'is_active', 'employee_id']);
            $userData['name_en'] = Str::upper($request->name_en);
            $userData['dob'] = Carbon::parse($userData['dob'])->format('Y-m-d');
            $userData['join_date'] = $userData['join_date'] ? Carbon::parse($userData['join_date'])->format('Y-m-d') : null;
            $userData['password'] = Hash::make($defaultPassword);
            $userData['username'] = 'default';
            $userData['branch_id'] = $branchId;

            $users = User::create($userData);
            $branch = Branch::findOrFail($branchId);
            $position = Position::findOrFail($request->position_id);

            // A one-word English name has no second part: use the first
            $lowerString = !empty($name[1]) ? $name[1] . $users->id : $name[0] . $users->id;

            $users->code = $branch->abbr . "-" . str_pad($users->id, 6, '0', STR_PAD_LEFT);

            $users->email = $lowerString . $companyName;
            $users->username = $lowerString;
            $users->save();

            $role = Role::find($request->role_id);
            $users->addRole($role);

            // Created from an employee's user request: link the account to
            // the employee and close the request
            if ($request->employee_id) {
                $employee = Employee::findOrFail($request->employee_id);
                if (User::where('employee_id', $employee->id)->where('id', '!=', $users->id)->exists()) {
                    throw new \Exception('បុគ្គលិកនេះមានគណនីប្រើប្រាស់រួចហើយ!');
                }
                $users->employee_id = $employee->id;
                $users->save();
                UserAccessRequest::where('employee_id', $employee->id)->where('status', true)->update(['status' => false]);
            }

            $userData['user_id'] = $users->id;

            if ($userData['manage_branch'] == 2 || $userData['manage_branch'] == 4) {
                foreach ($userData['_branch_id'] as $branch_id) {
                    UserBranch::create([
                        'user_id' => $userData['user_id'],
                        'branch_id' => $branch_id
                    ]);
                }
            }

            $settings = Setting::whereIn('key', ['default_request_user_group_id', 'company_name', 'telegram_bot_token', 'system_url'])->pluck('value', 'key');

            $groupId = $settings['default_request_user_group_id'] ?? null;
            $botToken = $settings['telegram_bot_token'] ?? null;
            $url = $settings['system_url'] ?? null;

            $esc = fn($v) => htmlspecialchars((string)($v ?? '-'), ENT_NOQUOTES, 'UTF-8');
            $b = fn($v) => '<b>' . $esc($v) . '</b>';
            $c = fn($v) => '<code>' . $esc($v) . '</code>';

            $loginUrl = $url
                ? (Str::startsWith($url, ['http://', 'https://']) ? $url : 'https://' . ltrim($url, '/'))
                : null;

            $codeMsg = "👤 " . $b("ព័ត៌មានអ្នកប្រើប្រាស់") . "\n";
            $codeMsg .= "━━━━━━━━━━━━━━━\n";
            $codeMsg .= "ឈ្មោះ៖ " . $b($users->name_kh) . "\n";
            $codeMsg .= "លេខសំគាល់៖ " . $b($users->code) . "\n";
            $codeMsg .= "តួនាទី៖ " . $esc($position->name_kh) . " (" . $esc($position->abbr) . ")\n";
            $codeMsg .= "សាខា៖ " . $esc($branch->name_kh) . "\n\n";

            $codeMsg .= "🔑 " . $b("ព័ត៌មានសម្រាប់ចូលប្រព័ន្ធ") . "\n";
            $codeMsg .= "━━━━━━━━━━━━━━━\n";
            $codeMsg .= "👇 ចុចពីលើ ដើម្បីចម្លង\n\n";

            $codeMsg .= "👤 <b>Username</b>\n";
            $codeMsg .= "👉 " . $c($users->username) . "\n\n";

            $codeMsg .= "🔐 <b>Password</b>\n";
            $codeMsg .= "👉 " . $c($defaultPassword) . "\n\n";

            if ($loginUrl) {
                $codeMsg .= "🌐 " . $b("ចូលប្រព័ន្ធតាម Chrome") . "\n";
                $codeMsg .= "👉 " . $c($loginUrl) . "\n\n";
            }
            $codeMsg .= "━━━━━━━━━━━━━━━\n";
            $codeMsg .= "⚠️ " . $b("សូមប្តូរលេខសម្ងាត់ភ្លាមៗ បន្ទាប់ពីចូលប្រព័ន្ធលើកដំបូង");

            try {
                if ($groupId && $botToken) {
                    $this->telegramService->sendMessage($codeMsg, [$groupId], $botToken, 'HTML');
                }
            } catch (\Throwable $e) {
                Log::warning('Login Telegram alert failed: ' . $e->getMessage());
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Successful Created User!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $this->guardSuperTarget($request);
        $this->guardAccessGrant($request, $request->integer('choose_branch_id'), $request->integer('manage_branch'));

        DB::beginTransaction();
        try {
            $users = User::findOrFail($request->id);

            $branch = Branch::findOrFail($request->choose_branch_id);

            $code = $branch->abbr . "-" . str_pad($users->id, 6, '0', STR_PAD_LEFT);
            $users->update([
                'code' => $code,
                'branch_id' => $request->choose_branch_id,
                'name_kh' => $request->name_kh,
                'name_en' => Str::upper($request->name_en),
                'manage_branch' => $request->manage_branch,
                'under_user_id' => $request->under_user_id,
                'position_id' => $request->position_id,
                'role_id' => $request->role_id,
                // System the user lands on after login; kept when not sent
                'default_part' => $request->default_part ?? $users->default_part,
                'gender' => $request->gender,
                'dob' => $request->dob ? Carbon::parse($request->dob)->format('Y-m-d') : null,
                'join_date' => $request->join_date ? Carbon::parse($request->join_date)->format('Y-m-d') : null,
                'contact' => $request->contact,
                'village_code' => $request->village_code,
                'national_id_number' => $request->national_id_number,
                'national_id_issue_date' => $request->national_id_issue_date ? Carbon::parse($request->national_id_issue_date)->format('Y-m-d') : null,
            ]);

            $users->syncRoles([$request->role_id]);

            if ($request->manage_branch == 2 || $request->manage_branch == 4) {

                UserBranch::where('user_id', $request->id)->delete();

                foreach ($request->_branch_id as $branch_id) {
                    UserBranch::create([
                        'user_id' => $request->id,
                        'branch_id' => $branch_id
                    ]);
                }
            }
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Successful Updated User!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();
        try {
            User::findOrFail($request->id)->delete();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Successful Deleted User!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function disable(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);
            $user->is_active = $user->is_active == true ? false : true;
            $user->save();

            $user->tokens()->update(['revoked' => true]);

            return response()->json([
                'status' => true,
                'message' => $user->is_active == 1 ? "Successful Opened User!" : "Successful Disabled User!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function password(UserPasswordRequest $request)
    {
        try {
            $user = User::findOrFail(Auth::id());

            if (!Hash::check($request->old_password, $user->password)) {
                abort(500, 'Old Password is incorrect!');
            }

            $user->password = bcrypt($request->new_password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => "Successful Change Password User!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }



    public function changePassword(Request $request)
    {
        $this->guardSuperTarget($request);

        try {
            $request->validate([
                'password' => 'required|string|min:8',
            ]);

            $user = User::findOrFail($request->id);

            $user->password = bcrypt($request->password);

            $user->save();

            return response()->json([
                'status' => true,
                'message' => "Successful Change Password User!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function changeUsernameEmail(Request $request)
    {
        $this->guardSuperTarget($request);

        try {
            $request->validate([
                'username' => 'required',
            ]);

            $user = User::findOrFail($request->id);

            $user->username = $request->username;
            $user->email = $request->email;

            $user->save();

            return response()->json([
                'status' => true,
                'message' => "Successful Change Username&Email User!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function permission(Request $request)
    {
        $this->guardSuperTarget($request);

        $request->validate([
            'permissions' => 'required|array',
            'permissions.*.id' => 'nullable|exists:permissions,id',
        ]);
        DB::beginTransaction();
        try {
            $user = User::findOrFail($request->id);

            $permissions = array_values($request->permissions);
            // Filter out invalid values
            $permissions = array_filter($permissions, function ($value) {
                return is_int($value) || is_string($value);
            });
            $user->permissions()->sync($permissions);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => "Successful Change Permission!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function changeImagePath(Request $request)
    {
        try {

            User::findOrFail(Auth::id())->update([
                'image_path' => $request->image_path ? 'storage/' . $this->storeImage($request->image_path, 'users/images') : null
            ]);

            return response()->json([
                'status' => true,
                'message' => "Successful Change Image!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function linkUser(Request $request)
    {
        $this->guardAccessGrant($request, $request->integer('branch_id'), $request->integer('manage_branch'));

        DB::beginTransaction();
        try {

            $findUser = User::findOrFail($request->id);
            $companyName = Cache::remember('setting_company_name', 86400, function () {
                return Setting::where('key', 'company_email')->value('value');
            });
            $defaultSubUserPassword = Cache::remember('setting_default_password', 86400, function () {
                return Setting::where('key', 'default_password')->value('value');
            });
            $username = Str::uuid();

            $insert = [
                'branch_id' => $request->branch_id,
                'manage_branch' => $request->manage_branch,
                'role_id' => $request->role_id,
                'position_id' => $request->position_id,


                'name_kh' => $findUser->name_kh,
                'name_en' => $findUser->name_en,
                'gender' => $findUser->gender,
                'dob' => $findUser->dob,

                'contact' => $findUser->contact,
                'parent_user_id' => $findUser->id,
                'join_date' => $findUser->join_date,
                'under_user_id' => $findUser->under_user_id,
                'type' => 'sub',
                'username' => $username,
                'email' => Str::random(8) . "@" . $companyName,
                'password' => Hash::make($defaultSubUserPassword),
            ];

            $user = User::create($insert);
            $role = Role::find($request->role_id);
            $user->addRole($role);

            $branch = Branch::find($request->branch_id);

            $user->code = $branch->abbr . '-' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
            $user->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'data' => $insert,
                'message' => "Successful Change Link!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function diagram(Request $request)
    {
        $users = User::query()
            ->leftJoin('positions as p', 'users.position_id', 'p.id')
            ->leftJoin('branches as b', 'users.branch_id', 'b.id')
            ->select([
                'users.id',
                'users.code',
                'users.name_kh',
                'users.name_en',
                'users.gender',
                'users.image_path',
                'users.under_user_id',
                'p.name_kh as position_name_kh',
                'p.name_en as position_name_en',
                'p.abbr as position_abbr',
                'p.level as position_level',
                'b.name_kh as branch_name_kh',
                'b.name_en as branch_name_en',
            ])
            ->whereBranch($this->getBranch())
            ->where('users.is_active', true)
            ->where('users.id', '!=', 1)
            ->orderByDesc('p.level')
            ->orderBy('users.name_en')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $users
        ]);
    }

    public function changeUnderUser(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'under_user_id' => 'nullable|exists:users,id|different:id',
        ]);

        try {
            $user = User::findOrFail($request->id);

            // Prevent cycles: the new supervisor must not be this user or one of their subordinates
            $parentId = $request->under_user_id;
            $seen = [];
            while ($parentId && !in_array($parentId, $seen)) {
                if ($parentId == $user->id) {
                    abort(422, 'Cannot move a user under their own subordinate!');
                }
                $seen[] = $parentId;
                $parentId = User::whereKey($parentId)->value('under_user_id');
            }

            $user->update(['under_user_id' => $request->under_user_id]);

            return response()->json([
                'status' => true,
                'message' => "Successful Changed Under User!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
