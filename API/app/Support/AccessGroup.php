<?php

namespace App\Support;

/**
 * Named groups of roles from config/roles.php, so routes never hard-code role names.
 *
 *   Route::middleware(AccessGroup::middleware(AccessGroup::ADMIN_AREA))
 */
final class AccessGroup
{
    public const ADMIN_AREA = 'admin_area';

    public const ADMIN_ONLY = 'admin_only';

    /** @return list<string> */
    public static function roles(string $group): array
    {
        return config("roles.groups.{$group}", []);
    }

    /** Laratrust middleware, e.g. "role:admin|teacher". */
    public static function middleware(string $group): string
    {
        return 'role:'.implode('|', self::roles($group));
    }
}
