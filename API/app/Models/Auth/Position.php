<?php

namespace App\Models\Auth;

use App\Models\Core\Department;
use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'name_kh',
        'name_en',
        'abbr',
        'level',
        'department_id',
        'is_leader',
        'is_member',
        'is_active',
        'insurance_amount',
        'position_fee',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_leader' => 'boolean',
        'is_member' => 'boolean',
        'insurance_amount' => 'float',
        'position_fee' => 'float'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
