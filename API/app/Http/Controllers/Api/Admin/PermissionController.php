<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $permissions = Permission::all();
            $result['data'] = $permissions;
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function show(Request $request)
    {
    }

    public function list(Request $request)
    {
    }

    public function store(Request $request)
    {
    }

    public function update(Request $request)
    {
    }

    public function delete(Request $request)
    {
    }

    public function disable(Request $request)
    {
    }

}
