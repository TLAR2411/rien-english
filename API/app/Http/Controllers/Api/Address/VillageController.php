<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Address\Village;
use Illuminate\Http\Request;

class VillageController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $villages = Village::query()
                ->where('is_active', true)->get();
            $result['data'] = $villages;
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
            $villages = Village::findOrFail($request->id);
            $result['data'] = $villages;
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
            $villages = Village::query()
                ->filter($request->input('filter', []))
                ->with('commune.district.province')
                ->getPaginate($request->limit);
            $villages = DataTableResource::collection($villages)->response()->getData(true);
            $result['data'] = $villages;
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
            $lastCode = Village::where('commune_code', $request->commune_code)->max('code') + 1;

            Village::create([
                'code' => $lastCode,
                'name_kh' => $request->name_kh,
                'name_en' => $request->name_en,
                'commune_code' => $request->commune_code
            ]);

            $result['message'] = "Successfully created village";
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

            Village::findOrFail($request->id)
                ->update([
                    'name_kh' => $request->name_kh,
                    'name_en' => $request->name_en,
                    'commune_code' => $request->commune_code
                ]);

            $result['message'] = "Successfully updated village";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function delete(Request $request) {}

    public function disable(Request $request) {}
}
