<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use \App\Models\Concerns\ScopesBranch;

    protected $fillable = [
        'name_kh',
        'name_en',
        'abbr',
        'contact',
        'house_no',
        'street',
        'village_code',
        'region',
        'start_date',
        'location',
        // App check-in: branch position and allowed distance
        'latitude',
        'longitude',
        'check_in_radius_m',
        'require_check_in_area',
    ];

    protected $casts = [
        'require_check_in_area' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected function branchColumn(): string
    {
        return 'branches.id';
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
