<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laratrust\Models\Role as RoleModel;

class Role extends RoleModel
{
    protected $fillable = [
        'name',
        'display_name',
        'abbr'
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
