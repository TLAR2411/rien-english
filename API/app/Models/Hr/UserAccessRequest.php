<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;

/**
 * A request to make a login account for an employee, raised when the
 * employee is created. status: true = still waiting, false = closed (the
 * user was created, or the request was dismissed).
 */
class UserAccessRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
