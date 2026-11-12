<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Role;
use App\Models\Auth\UserBranch;
use App\Models\Core\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = config('app.admin.password') ?: Str::password(16);
        if (! config('app.admin.password')) {
            $this->command?->warn("Generated admin password (shown once): {$password}");
        }

        $branch = Branch::where('is_head', true)->firstOrFail();
        $developer = Role::where('name', 'developer')->firstOrFail();

        $user = User::create([
            'code' => $branch->abbr.'-000001',
            'branch_id' => $branch->id,
            'name_kh' => 'Admin',
            'name_en' => 'Admin',
            'username' => 'admin',
            'email' => config('app.admin.email'),
            'password' => bcrypt($password),
            'is_super' => true,
            'manage_branch' => 3,
            'role_id' => $developer->id,
            'join_date' => now(),
            'default_part' => 'admin',
        ]);

        // `developer` keeps all old permissions; `admin` opens the learning admin area
        $user->addRoles([$developer, Role::where('name', 'admin')->firstOrFail()]);

        UserBranch::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
        ]);
    }
}
