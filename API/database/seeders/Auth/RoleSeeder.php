<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $insert = [
            [
                'name' => 'developer',
                'display_name' => 'Developer',
                'abbr' => 'DEV',
            ],
            [
                'name' => 'system-administrator',
                'display_name' => 'System Administrator',
                'abbr' => 'SYS',
            ],
            [
                'name' => 'staff',
                'display_name' => 'បុគ្គលិក',
                'abbr' => 'STF',
            ],
            // Learning platform roles
            [
                'name' => 'admin',
                'display_name' => 'Admin',
                'abbr' => 'ADM',
            ],
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'abbr' => 'TCH',
            ],
            [
                'name' => 'student',
                'display_name' => 'Student',
                'abbr' => 'STU',
            ],
        ];

        foreach ($insert as $value) {
            Role::updateOrCreate(['name' => $value['name']], $value);
        }

        $role = Role::where('name', 'developer')->first();
        $role->syncPermissions(Permission::all());
    }
}
