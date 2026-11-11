<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Address\Commune;
use Illuminate\Http\Request;

class CommuneController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $communes = Commune::query()
                ->where('is_active', true)->get();
            $result['data'] = $communes;
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
            $commune = Commune::findOrFail($request->id);
            $result['data'] = $commune;
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
            $communes = Commune::query()
                ->with('district.province')
                ->filter($request->input('filter', []))
                ->getPaginate($request->limit);
            $communes = DataTableResource::collection($communes)->response()->getData(true);
            $result['data'] = $communes;
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
            $lastCode = Commune::where('district_code', $request->district_code)->max('code') + 1;

            Commune::create([
                'code' => $lastCode,
                'name_kh' => $request->name_kh,
                'name_en' => $request->name_en,
                'district_code' => $request->district_code
            ]);

            $result['message'] = "Successfully created commune";
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

            Commune::findOrFail($request->id)
                ->update([
                    'name_kh' => $request->name_kh,
                    'name_en' => $request->name_en,
                    'district_code' => $request->district_code
                ]);

            $result['message'] = "Successfully updated commune";
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }
        return response()->json($result);
    }

    public function delete(Request $request) {}

    public function disable(Request $request) {}
}
