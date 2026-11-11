<?php

namespace App\Actions\Auth;

use App\Models\Auth\Role;
use App\Models\Auth\UserBranch;
use App\Models\Core\Branch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The one place that creates a student account (used by sign-up).
 * Fills the required user fields and gives the account the `student` role.
 */
class CreateStudentAccount
{
    public const ROLE = 'student';

    public function handle(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            $role = Role::where('name', self::ROLE)->firstOrFail();
            $branch = Branch::where('is_head', true)->first() ?? Branch::firstOrFail();

            $user = User::forceCreate([
                'branch_id' => $branch->id,
                'name_kh' => $name,
                'name_en' => $name,
                'email' => Str::lower($email),
                'username' => $this->uniqueUsername($email),
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'manage_branch' => 1,
                'default_part' => 'student',
                'join_date' => now(),
            ]);

            $user->code = $branch->abbr.'-'.str_pad($user->id, 6, '0', STR_PAD_LEFT);
            $user->save();

            $user->addRole($role);

            UserBranch::create(['user_id' => $user->id, 'branch_id' => $branch->id]);

            return $user;
        });
    }

    /** "Sok.Dara@gmail.com" → "sokdara", then "sokdara2", "sokdara3"… if taken. */
    private function uniqueUsername(string $email): string
    {
        $base = Str::lower(preg_replace('/[^A-Za-z0-9]/', '', Str::before($email, '@'))) ?: 'student';
        $username = $base;

        for ($i = 2; User::where('username', $username)->exists(); $i++) {
            $username = $base.$i;
        }

        return $username;
    }
}
