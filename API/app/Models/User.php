<?php

namespace App\Models;

use App\Models\Address\Village;
use App\Models\Auth\PermissionUser;
use App\Models\Auth\Role;
use App\Models\Auth\UserBranch;
use App\Models\Core\Branch;
use App\Traits\TracksUserActions;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements LaratrustUser
{
    use \App\Models\Concerns\ScopesBranch;
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, TracksUserActions;
    use HasRolesAndPermissions {
        hasPermission as protected laratrustHasPermission;
    }

    /**
     * Super users (is_super = true) are allowed every permission. Laratrust's
     * `permission:` route middleware and isAbleTo() both go through here.
     */
    public function hasPermission(
        string|array|\BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if ($this->is_super) {
            return true;
        }

        return $this->laratrustHasPermission($permission, $team, $requireAll);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name_kh',
        'name_en',
        'email',
        'password',
        'username',
        'is_super',
        'is_active',
        'manage_branch',
        'branch_id',
        'role_id',
        'gender',
        'contact',
        'national_id_number',
        'national_id_issue_date',
        'join_date',
        'village_code',
        'dob',
        'default_part',
        'image_path',
        'parent_user_id',
        'type',
        'last_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'created_at',
        'updated_at',
        'deleted_by',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Automatically hash the password when it is set.
     *
     * @param  string  $value
     * @return void
     */
    // public function setPasswordAttribute($value)
    // {
    //     $this->attributes['password'] = bcrypt($value);
    // }

    protected static $recordEvents = ['created', 'updated', 'deleted'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()                   // Log all attributes
            ->useLogName('users')  // Optional log name
            ->dontSubmitEmptyLogs();     // Avoid empty logs
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        // ✅ Get the user's Khmer name or fallback to 'System'
        $userName = auth()->user()?->name_kh ?? 'System';

        // ✅ Translate the action
        $action = match ($eventName) {
            'created' => 'បង្កើត',
            'updated' => 'កែប្រែ',
            'deleted' => 'លុប',
            default => 'ធ្វើប្រតិបត្តិការលើ',
        };

        // ✅ Build the activity description
        $activity->description = "{$userName} បាន{$action} អ្នកប្រើប្រាស់ {$this->name_kh} {$this->code}";
    }

    protected function branchColumn(): string
    {
        return 'users.branch_id';
    }

    #[Scope]
    public function filter($query, array $filters)
    {
        $query->when(! empty($filters['search']), function ($query) use ($filters) {
            return $query->where(function ($q) use ($filters) {
                $searchTerm = '%'.trim($filters['search']).'%';
                // Phone numbers are typed with or without spaces
                $digits = preg_replace('/\D/', '', $filters['search']);

                $q->where('name_kh', 'like', $searchTerm)
                    ->orWhere('code', 'like', $searchTerm)
                    ->orWhere('name_en', 'like', $searchTerm)
                    ->orWhere('username', 'like', $searchTerm)
                    ->orWhere('email', 'like', $searchTerm)
                    ->orWhere('national_id_number', 'like', $searchTerm);

                if (strlen($digits) >= 3) {
                    $q->orWhereRaw("REPLACE(contact, ' ', '') LIKE ?", ["%{$digits}%"]);
                }

                return $q;
            });
        });

        // Active / disabled account
        $query->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        });

        $query->when(! empty($filters['role_id']), fn ($q) => $q->where('role_id', $filters['role_id']));
        $query->when(! empty($filters['gender']), fn ($q) => $q->where('gender', $filters['gender']));

        // Join date range
        $query->when(! empty($filters['join_from']), fn ($q) => $q->whereDate('join_date', '>=', $filters['join_from']));
        $query->when(! empty($filters['join_to']), fn ($q) => $q->whereDate('join_date', '<=', $filters['join_to']));

        // Last login: today | 7 | 30 (days) | inactive_30 (not in 30 days) | never
        $query->when(! empty($filters['last_login']), function ($query) use ($filters) {
            match ((string) $filters['last_login']) {
                'today' => $query->whereDate('last_login', today()),
                '7', '30' => $query->where('last_login', '>=', now()->subDays((int) $filters['last_login'])),
                'inactive_30' => $query->where('last_login', '<', now()->subDays(30)),
                'never' => $query->whereNull('last_login'),
                default => null,
            };
        });
    }

    public function findForPassport(string $username)
    {
        return $this->where('username', $username)->orWhere('email', $username)->first();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class);
    }

    public function userPermission()
    {
        return $this->hasMany(PermissionUser::class);
    }

    public function userBranch()
    {
        return $this->hasMany(UserBranch::class, 'id', 'user_id');
    }

    public function mainUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id')
            ->where('is_active', true)
            ->orderByDesc('created_at');
    }

    public function subUsers()
    {
        return $this->hasMany(User::class, 'parent_user_id')
            ->where('id', '!=', auth()->id())
            ->where('is_active', true)
            ->orderByDesc('created_at');
    }

    public function siblings()
    {
        return $this->hasMany(User::class, 'parent_user_id', 'parent_user_id')
            ->where('id', '!=', auth()->id())
            ->where('is_active', true)
            ->orderByDesc('created_at');
    }
}
