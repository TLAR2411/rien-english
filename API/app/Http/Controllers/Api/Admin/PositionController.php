<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Auth\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $positions = Position::query()
                ->where('is_active', true)
                ->orderBy('positions.department_id')->get();
            $result['data'] = $positions;
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
            $position = Position::findOrFail($request->id);
            $result['data'] = $position;
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
            $positions = Position::with('department')->getPaginate($request->limit);
            $positions = DataTableResource::collection($positions)->response()->getData(true);
            $result['data'] = $positions;
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function store(Request $request)
    {
        $result['status'] = 200;
        try {
            Position::create($request->all());
            $result['message'] = "Successful Created Position!";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function update(Request $request)
    {
        $result['status'] = 200;
        try {
            $position = Position::findOrFail($request->id);
            $position->name_kh = $request->name_kh;
            $position->name_en = $request->name_en;
            $position->abbr = $request->abbr;
            $position->level = $request->level;
            $position->department_id = $request->department_id;
            $position->insurance_amount = $request->insurance_amount;
            $position->position_fee = $request->position_fee;
            // Loan-system roles (credit officer / team leader); keep as-is when not sent
            if ($request->has('is_member')) $position->is_member = $request->boolean('is_member');
            if ($request->has('is_leader')) $position->is_leader = $request->boolean('is_leader');
            $position->save();
            $result['message'] = "Successful Updated Position!";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function delete(Request $request)
    {
        $result['status'] = 200;
        try {
            Position::findOrFail($request->id)->delete();
            $result['message'] = "Successful Deleted Position!";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function disable(Request $request) {}
}
