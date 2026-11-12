<?php

use App\Models\Auth\Permission;
use App\Models\Auth\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * T34 — permissions for the Writing Review screen: view-writing, review-writing.
 * Added to the admin + teacher roles WITHOUT changing their other permissions
 * (RoleSeeder would reset every permission to config/roles.php).
 * New installs get them from PermissionSeeder + RoleSeeder too.
 */
return new class extends Migration
{
    private const NAMES = ['view' => 'view-writing', 'review' => 'review-writing'];

    public function up(): void
    {
        // unguarded: `group` is not a fillable column of Laratrust's Permission model
        $ids = Permission::unguarded(fn () => collect(self::NAMES)->map(fn ($name, $item) => Permission::updateOrCreate(
            ['name' => $name],
            ['group' => 'writing', 'display_name' => $item],
        )->id)->values()->all());

        Role::whereIn('name', ['admin', 'teacher'])->get()->each(function (Role $role) use ($ids) {
            $role->permissions()->syncWithoutDetaching($ids);
            $role->flushCache();
        });
    }

    public function down(): void
    {
        Permission::whereIn('name', self::NAMES)->get()->each(function (Permission $permission) {
            $permission->roles()->detach();
            $permission->delete();
        });
    }
};
