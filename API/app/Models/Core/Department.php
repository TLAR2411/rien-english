<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = [
        'name_kh',
        'name_en',
        'is_active'
    ];

    protected $hidden = [
        'is_active',
        'created_at',
        'updated_at',
    ];
}
