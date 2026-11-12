<?php

namespace App\Http\Controllers\Api\Address;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Address\Province;
use Illuminate\Http\Request;

class ProvinceController extends Controller
{
    public function all(Request $request)
    {
        $result['status'] = 200;
        try {
            $provinces = Province::query()
                ->where('is_active', true)->get();
            $result['data'] = $provinces;
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
            $provinces = Province::findOrFail($request->id);
            $result['data'] = $provinces;
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
            $provinces = Province::query()
                ->filter($request->input('filter', []))
                ->getPaginate($request->limit);
            $provinces = DataTableResource::collection($provinces)->response()->getData(true);
            $result['data'] = $provinces;
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
            $province = Province::create($request->all());
            $province->code = $province->id;
            $province->save();
            $result['message'] = 'Successfully create province.';
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
            $province = Province::findOrFail($request->id);
            $province->update($request->all());
            $province->save();
            $result['message'] = 'Successfully update province.';
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
            Province::findOrFail($request->id)->delete();
            $result['message'] = 'Successfully delete province.';
        } catch (\Throwable $th) {
            $result['status'] = 500;
            $result['message'] = $th->getMessage();
        }

        return response()->json($result);
    }

    public function disable(Request $request) {}
}
