<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Core\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function all(Request $request)
    {
        try {
            $departments = Department::query()
                ->where('is_active', true)->get();

            return response()->json([
                'status' => true,
                'data' => $departments
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $departments = Department::findOrFail($request->id);
            return response()->json([
                'status' => true,
                'data' => $departments
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function list(Request $request)
    {
        try {
            $departments = Department::paginate($request->limit);
            $departments = DataTableResource::collection($departments)->response()->getData(true);
            return response()->json([
                'status' => true,
                'data' => $departments
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            Department::create($request->all());

            return response()->json([
                'status' => true,
                'message' => "Successful Created Department!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function update(Request $request)
    {
        try {
            $department = Department::findOrFail($request->id);
            $department->name_kh = $request->name_kh;
            $department->name_en = $request->name_en;
            $department->save();

            return response()->json([
                'status' => true,
                'message' => "Successful Updated Department!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function delete(Request $request)
    {
        try {
            Department::findOrFail($request->id)->delete();

            return response()->json([
                'status' => true,
                'message' => "Successful Deleted Department!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
    public function disable(Request $request)
    {
        try {
            $department = Department::findOrFail($request->id);
            $department->is_active = $department->is_active == true ? false : true;
            $department->save();


            return response()->json([
                'status' => true,
                'message' => "Successful " . $department->is_active == 1 ? "Undisabled" : "Disabled" . " Department!"
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
