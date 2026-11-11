<?php

namespace Database\Seeders\Auth;

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Seeder;

/**
 * Creates the roles and gives them their permissions.
 * Which permissions each role gets is set in config/roles.php.
 * Safe to run again: it updates existing roles.
 */
class RoleSeeder extends Seeder
{
    private const ROLES = [
        // Old system roles (kept so existing accounts keep working)
        ['name' => 'developer', 'display_name' => 'Developer', 'abbr' => 'DEV'],
        ['name' => 'system-administrator', 'display_name' => 'System Administrator', 'abbr' => 'SYS'],
        ['name' => 'staff', 'display_name' => 'បុគ្គលិក', 'abbr' => 'STF'],

        // Learning platform roles
        ['name' => 'admin', 'display_name' => 'Admin', 'abbr' => 'ADM'],
        ['name' => 'teacher', 'display_name' => 'Teacher', 'abbr' => 'TCH'],
        ['name' => 'student', 'display_name' => 'Student', 'abbr' => 'STU'],
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }

        Role::where('name', 'developer')->firstOrFail()->syncPermissions(Permission::all());

        foreach (config('roles.permissions') as $roleName => $rule) {
            Role::where('name', $roleName)->firstOrFail()->syncPermissions($this->permissionsFor($rule));
        }
    }

    /** '*' = all permissions; otherwise a list of permission groups or names. */
    private function permissionsFor(string|array $rule)
    {
        if ($rule === '*') {
            return Permission::all();
        }

        return Permission::whereIn('group', $rule)->orWhereIn('name', $rule)->get();
    }
}
