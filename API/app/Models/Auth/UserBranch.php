<?php

namespace App\Models\Auth;

use Illuminate\Database\Eloquent\Model;

class UserBranch extends Model
{
    protected $table = 'user_branches';
    protected $fillable = [
        'user_id',
        'branch_id'
    ];
}
