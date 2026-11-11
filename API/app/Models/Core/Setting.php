<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
