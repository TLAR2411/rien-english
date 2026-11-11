<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Auth\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $roles = Role::all();

            $result['data'] = $roles;
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function show(Request $request)
    {
        $result['status'] = 200;
        try {
            $roles = Role::findOrFail($request->id);
            $rolePermission = DB::table('permission_role')
                ->select('permission_id as id')
                ->where('role_id', $request->id)
                ->get();
            $result['data'] = [
                'role_data' => $roles,
                'role_permission' => $rolePermission
            ];
            // $result['data'] = $roles;
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function list(Request $request)
    {
        $result['status'] = 200;
        try {
            $roles = Role::paginate($request->limit);
            $roles = DataTableResource::collection($roles)->response()->getData(true);
            $result['data'] = $roles;
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $result['status'] = 200;
        DB::beginTransaction();
        try {
            $roleData = $request->roleData;
            $role = Role::create($roleData);
            $role->syncPermissions($request->permissions);
            DB::commit();
            $result['message'] = "Successful Created Role!";
        } catch (\Throwable $th) {
            DB::rollBack();
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function update(Request $request)
    {
        $request->validate([
            'roleData.id' => 'required|exists:roles,id',
            'roleData.name' => 'required|string',
            'roleData.display_name' => 'required|string',
            'roleData.abbr' => 'nullable|string',
            'permissions' => 'required|array',
            'permissions.*.id' => 'nullable|exists:permissions,id',
        ]);

        DB::beginTransaction();
        try {
            $roleData = $request->roleData;
            $role = Role::findOrFail($roleData['id']);
            $role->name = $roleData['name'];
            $role->display_name = $roleData['display_name'];
            $role->abbr = $roleData['abbr'];
            $role->save();

            // Transform permissions to flat array
            $permissions = array_values($request->permissions);
            // Filter out invalid values
            $permissions = array_filter($permissions, function ($value) {
                return is_int($value) || is_string($value);
            });

            $role->syncPermissions($permissions);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Successfully Updated Role!"
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => $permissions
            ]);
        }
    }

    public function delete(Request $request) {}

    public function disable(Request $request) {}
}
