<?php

namespace App\Models\Hr;

use App\Models\Address\Village;
use App\Models\Auth\Position;
use App\Models\Core\Branch;
use App\Models\Core\Department;
use App\Models\User;
use App\Traits\TracksUserActions;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Employee extends Model
{
    use \App\Models\Concerns\ScopesBranch;

    use TracksUserActions;

    protected $fillable = [
        'code',
        'branch_id',
        'name_kh',
        'name_en',
        'department_id',
        'position_id',
        'gender',
        'dob',
        'marital_status',
        'dependent_children',
        'spouse_is_dependent',
        'contact',
        'parent_contact',
        'national_id_number',
        'national_id_issue_date',
        'bank_account_number',
        'house_no',
        'street',
        'village_code',
        'hired_date',
        'terminated_date',
        'last_working_date',
        'termination_type',
        'termination_reason',
        'employee_type',
        'contract_type',
        'work_period_id',
        'probation_end_date',
        'work_email',
        'under_employee_id',
        'image_path',
        'image_path_document',
        'is_home_visited',
        'home_visited_date',
        'is_active',
    ];

    protected function branchColumn(): string
    {
        return 'employees.branch_id';
    }

    #[Scope]
    public function filter($query, array $filters)
    {
        $query->when(!empty($filters['search']), function ($query) use ($filters) {
            $search = '%' . trim($filters['search']) . '%';
            return $query->where(function ($query) use ($search) {
                return $query->where('employees.name_kh', 'like', $search)
                    ->orWhere('employees.name_en', 'like', $search)
                    ->orWhere('employees.code', 'like', $search)
                    ->orWhere('employees.contact', 'like', $search)
                    ->orWhere('employees.national_id_number', 'like', $search);
            });
        })
            // 1 = active, 0 = inactive; empty = all
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                return $query->where('employees.is_active', (int)$filters['is_active']);
            })
            ->when(!empty($filters['position_id']), function ($query) use ($filters) {
                return $query->where('employees.position_id', $filters['position_id']);
            })
            // current = still employed, former = contract terminated; empty = all
            ->when(($filters['employment'] ?? '') === 'current', fn($query) => $query->whereNull('employees.terminated_date'))
            ->when(($filters['employment'] ?? '') === 'former', fn($query) => $query->whereNotNull('employees.terminated_date'))
            ->when(!empty($filters['termination_type']), function ($query) use ($filters) {
                return $query->where('employees.termination_type', $filters['termination_type']);
            })
            // Left between these dates (by last working date)
            ->when(!empty($filters['left_from']), fn($query) => $query->whereDate('employees.last_working_date', '>=', $filters['left_from']))
            ->when(!empty($filters['left_to']), fn($query) => $query->whereDate('employees.last_working_date', '<=', $filters['left_to']));
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }


    public function village()
    {
        return $this->belongsTo(Village::class, 'village_code', 'code');
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    // Login account linked to this employee (holds the profile photo)
    public function user()
    {
        return $this->hasOne(User::class, 'employee_id');
    }
}
