<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Role;
use App\Models\Auth\UserBranch;
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
        $password = env('ADMIN_PASSWORD') ?: Str::password(16);
        if (! env('ADMIN_PASSWORD')) {
            $this->command?->warn("Generated admin password (shown once): {$password}");
        }

        $user = User::create([
            'code' => 'B1-000001',
            'branch_id' => 1,
            'name_kh' => 'Admin',
            'name_en' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt($password),
            'is_super' => true,
            'manage_branch' => 3,
            'role_id' => 1,
            'join_date' => now(),
            'default_part' => 'admin',
        ]);

        $role = Role::find(1);
        $user->addRole($role);

        UserBranch::create([
            'user_id' => $user->id,
            'branch_id' => 1,
        ]);
    }
}
