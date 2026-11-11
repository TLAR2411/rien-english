<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Routing\Controllers\Middleware;

/**
 * Checks the permission for each controller action from one group name:
 *   index/show → view-{group}, store → add-{group},
 *   update/move → edit-{group}, destroy → delete-{group}.
 *
 * Use it with `implements HasMiddleware` and a `PERMISSION_GROUP` constant.
 * Permissions are created in PermissionSeeder and given to roles in config/roles.php.
 */
trait ChecksCrudPermissions
{
    public static function middleware(): array
    {
        $group = static::PERMISSION_GROUP;

        return [
            new Middleware("permission:view-{$group}", only: ['index', 'show', 'all']),
            new Middleware("permission:add-{$group}", only: ['store']),
            new Middleware("permission:edit-{$group}", only: ['update', 'move']),
            new Middleware("permission:delete-{$group}", only: ['destroy']),
        ];
    }
}
