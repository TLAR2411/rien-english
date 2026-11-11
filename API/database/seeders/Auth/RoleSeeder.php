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
                'abbr' => 'DEV'
            ],
            [
                'name' => 'system-administrator',
                'display_name' => 'System Administrator',
                'abbr' => 'SYS'
            ],
            [
                'name' => 'human-resource-manager',
                'display_name' => 'ប្រធាននាយកដ្ឋានធនធានមនុស្ស',
                'abbr' => 'HHR'
            ],
            [
                'name' => 'staff',
                'display_name' => 'បុគ្គលិក',
                'abbr' => 'STF'
            ],
        ];

        foreach ($insert as $value) {
            Role::create($value);
        }

        $role = Role::where('name', 'developer')->first();
        $role->syncPermissions(Permission::all());
    }
}
