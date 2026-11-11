<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name_kh',
        'name_en',
        'contact',
        'email',
        'address',
        'logo_path',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
