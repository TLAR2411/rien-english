<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Hr\Employee;
use App\Models\Hr\UserAccessRequest;
use Illuminate\Http\Request;

/**
 * Requests to make a login account for a new employee (raised by HR when the
 * employee is created). Admins see the waiting ones, then create the user
 * from the employee's details or dismiss the request.
 */
class UserAccessRequestController extends Controller
{
    /** Waiting requests, newest first. */
    public function list(Request $request)
    {
        try {
            $search = trim((string)($request->input('filter.search') ?? ''));

            $list = UserAccessRequest::query()
                ->with(['employee.branch', 'employee.position'])
                ->where('status', true)
                ->when($search !== '', function ($query) use ($search) {
                    $query->whereHas('employee', function ($q) use ($search) {
                        $like = '%' . $search . '%';
                        $q->where('name_kh', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('code', 'like', $like)
                            ->orWhere('contact', 'like', $like);
                    });
                })
                ->orderByDesc('id')
                ->getPaginate($request->limit);
            $list = DataTableResource::collection($list)->response()->getData(true);

            return response()->json([
                'status' => true,
                'data' => $list,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /** How many are waiting (menu badge / reminders). */
    public function count()
    {
        return response()->json([
            'status' => true,
            'data' => UserAccessRequest::where('status', true)->count(),
        ]);
    }

    /**
     * The employee's details for the create-user form. Only the fields that
     * form uses: no salary or other HR data.
     */
    public function employee(Request $request)
    {
        try {
            $employee = Employee::query()
                ->select([
                    'id', 'code', 'branch_id', 'name_kh', 'name_en', 'position_id', 'gender', 'dob',
                    'contact', 'national_id_number', 'national_id_issue_date', 'hired_date',
                ])
                ->findOrFail($request->employee_id);

            return response()->json([
                'status' => true,
                'data' => $employee,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /** Close a request without creating a user. */
    public function dismiss(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        try {
            UserAccessRequest::findOrFail($request->id)->update(['status' => false]);

            return response()->json([
                'status' => true,
                'message' => 'Successful dismiss user request!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
