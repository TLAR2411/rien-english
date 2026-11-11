<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    protected $table = 'communes';
    protected $fillable = [
        'name_kh',
        'name_en',
        'code',
        'district_code',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    #[Scope]
    public function filter($query, $filters)
    {
        return $query->when(!empty($filters['search']), function ($q) use ($filters) {
            $q->where(function ($query) use ($filters) {
                $query->where('name_kh', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('name_en', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('code', 'like', '%' . $filters['search'] . '%');
            });
        });
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_code', 'code');
    }
}
