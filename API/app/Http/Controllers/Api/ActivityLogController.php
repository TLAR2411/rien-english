<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function list(Request $request)
    {
        try {
            $filters = $request->input('filter', []);
            $subjectType = sprintf('App\\Models\\%s', $filters['subject_type'] ?? null);
            $activity = Activity::query()
                ->with(['causer', 'subject'])
                ->when(!empty($filters['user_id']), function ($query) use ($filters) {
                    return $query->where('causer_id', $filters['user_id']);
                })
                ->when(!empty($filters['event']), function ($query) use ($filters) {
                    return $query->where('event', $filters['event']);
                })
                ->when(!empty($filters['subject_type']), function ($query) use ($subjectType) {
                    return $query->where('subject_type', $subjectType);
                })
                ->when(!empty($filters['search']), function ($query) use ($filters) {
                    return $query->where(function ($q) use ($filters) {
                        $searchTerm = '%' . trim($filters['search']) . '%';
                        return $q->where('description', 'like', $searchTerm);
                    });
                })
                ->when(!empty($filters['start_date']), function ($query) use ($filters) {
                    return $query->where(function ($q) use ($filters) {
                        $searchTerm = '%' . Carbon::parse($filters['start_date'])->format("Y-m-d") . '%';
                        return $q->where('created_at', 'like', $searchTerm);
                    });
                })
                ->orderBy('id', 'desc')
                ->getPaginate($request->limit);
            $activity = DataTableResource::collection($activity)->response()->getData(true);

            return response()->json([
                'status' => true,
                'data' => $activity
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function deleteByDate(Request $request)
    {
        try {
            Activity::query()
                ->when($request->before_date, function ($query) use ($request) {
                    $query->where('created_at', '<=', Carbon::parse($request->before_date)->format('Y-m-d'));
                })
                ->delete();

            return response()->json([
                'status' => true,
                'message' => 'Successful Delete!'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
