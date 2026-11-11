<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Address\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $districts = District::query()
                ->where('is_active', true)->get();
            $result['data'] = $districts;
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
            $district = District::findOrFail($request->id);
            $result['data'] = $district;
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
            $district = District::query()
                ->with('province')
                ->filter($request->input('filter', []))
                ->getPaginate($request->limit);
            $district = DataTableResource::collection($district)->response()->getData(true);
            $result['data'] = $district;
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
            $lastCode = District::where('province_code', $request->province_code)->max('code') + 1;

            District::create([
                'code' => $lastCode,
                'name_kh' => $request->name_kh,
                'name_en' => $request->name_en,
                'province_code' => $request->province_code
            ]);

            $result['message'] = "Successfully created district";
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

            District::findOrFail($request->id)
                ->update([
                    'name_kh' => $request->name_kh,
                    'name_en' => $request->name_en,
                    'province_code' => $request->province_code
                ]);

            $result['message'] = "Successfully updated district";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function delete(Request $request) {}

    public function disable(Request $request) {}
}
