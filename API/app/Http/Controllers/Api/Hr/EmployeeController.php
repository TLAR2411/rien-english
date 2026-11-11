<?php

namespace App\Http\Controllers\Api\Hr;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Address\Commune;
use App\Models\Address\District;
use App\Models\Address\Province;
use App\Models\Address\Village;
use App\Models\Auth\Position;
use App\Models\Core\Branch;
use App\Models\Hr\Employee;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class EmployeeController extends Controller
{

    private userService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Save an employee photo sent as base64: cropped to 4x6 (2:3, portrait)
     * around the centre and kept at its own resolution - unlike storeImage(),
     * which shrinks every picture to 1024px. Returns the path on the public disk.
     */
    private function storeEmployeePhoto(string $inputImage): string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $inputImage, $type)) {
            abort(500, 'Invalid Base64 image format');
        }
        if (!in_array(strtolower($type[1]), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            abort(500, 'Unsupported image type');
        }
        $binary = base64_decode(substr($inputImage, strpos($inputImage, ',') + 1), true);
        if ($binary === false) {
            abort(500, 'Invalid Base64 image data');
        }

        $img = Image::read($binary)->orient();

        // Largest 2:3 area that fits the picture: nothing is scaled down
        $width = (int)min($img->width(), floor($img->height() * 2 / 3));
        $height = (int)floor($width * 3 / 2);
        $img = $img->cover($width, $height);

        $now = Carbon::now();
        $storagePath = 'images/employees/photos/' . $now->year . '/' . $now->month . '/' . $now->day . '/' . Str::uuid() . '.webp';
        Storage::disk('public')->put($storagePath, (string)$img->toWebp(92));

        return $storagePath;
    }

    public function show(Request $request)
    {
        try {
            $relations = ['branch', 'position.department', 'village.commune.district.province'];

            $employee = Employee::with($relations)->findOrFail($request->id);

            $village = Village::where('code', $employee->village_code ?? null)->first();
            $commune = Commune::where('code', $village->commune_code ?? null)->first();
            $district = District::where('code', $commune->district_code ?? null)->first();
            $province = Province::where('code', $district->province_code ?? null)->first();

            $employee['village_code'] = $village['code'] ?? null;
            $employee['commune_code'] = $commune['code'] ?? null;
            $employee['district_code'] = $district['code'] ?? null;
            $employee['province_code'] = $province['code'] ?? null;

            return response()->json([
                'status' => true,
                'data' => $employee
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
            $relations = ['branch', 'position.department', 'village.commune.district.province'];
            // ID cards: the photo comes from the employee's login account
            if ($request->boolean('with_photo')) {
                $relations[] = 'user:id,employee_id,image_path';
            }

            $list = Employee::query()
                ->with($relations)
                ->whereBranch($this->getBranch())
                // `employment` (current / former) is a fixed page parameter, kept
                // apart from the user's saved filters
                ->filter(array_merge($request->input('filter', []), $request->only('employment')))
                // Former employees: most recent leavers first
                ->when(
                    $request->input('employment') === 'former',
                    fn($q) => $q->orderByDesc('employees.last_working_date')
                )
                ->orderByDesc('employees.id')
                ->getPaginate($request->limit);
            $list = DataTableResource::collection($list)->response()->getData(true);

            return response()->json([
                'status' => true,
                'data' => $list
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $inputData = $request->all();
        $branchId = $this->currentBranchId();

        DB::beginTransaction();
        try {
            $inputData['branch_id'] = $branchId;
            // The form sends the photo as base64: store the file, keep its path
            $inputData['image_path'] = $request->image_path
                ? 'storage/' . $this->storeEmployeePhoto($request->image_path)
                : null;

            $employee = Employee::create($inputData);

            $branch = Branch::findOrFail($branchId);

            $employee->code = $branch->abbr . '-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT);
            $employee->save();

            $this->userService->requestUser([
                'employee_id' => $employee->id,
            ]);
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Successful Create Employee!',
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($request->id);
            $branch = Branch::findOrFail($request->choose_branch_id);

            $code = $branch->abbr . '-' . str_pad($employee->id, 4, '0', STR_PAD_LEFT);
            $employee->update([
                'code' => $code,
                'branch_id' => $request->choose_branch_id,
                'name_kh' => $request->name_kh,
                'name_en' => $request->name_en,
                'position_id' => $request->position_id,
                'gender' => $request->gender,
                'dob' => $request->dob ? Carbon::parse($request->dob)->format('Y-m-d') : null,
                'hired_date' => $request->hired_date ? Carbon::parse($request->hired_date)->format('Y-m-d') : null,
                'contact' => $request->contact,
                'parent_contact' => $request->parent_contact,
                'bank_account_number' => $request->bank_account_number,
                'village_code' => $request->village_code,
                'national_id_number' => $request->national_id_number,
                'national_id_issue_date' => $request->national_id_issue_date ? Carbon::parse($request->national_id_issue_date)->format('Y-m-d') : null,
            ]);

            // Photo: a new base64 picture replaces it, an empty value removes
            // it, the unchanged path is left alone
            if ($request->has('image_path') && $employee->image_path != $request->image_path) {
                if (!$request->image_path) {
                    $employee->update(['image_path' => null]);
                } elseif (str_starts_with($request->image_path, 'data:image/')) {
                    $employee->update(['image_path' => 'storage/' . $this->storeEmployeePhoto($request->image_path)]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Successful Create Employee!',
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
    }

    public function disable(Request $request)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($request->id);
            $employee->is_active = $employee->is_active ? false : true;
            $employee->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Successful Disable Employee!',
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function terminate(Request $request)
    {
        $request->validate([
            'terminated_date' => 'required|date',
            'last_working_date' => 'required|date|before_or_equal:terminated_date',
            'termination_type' => 'required|in:resignation,dismissal,end_of_contract,retirement,mutual_agreement,other',
            'termination_reason' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($request->id);

            if ($employee->terminated_date) {
                throw new \Exception('បុគ្គលិកនេះត្រូវបានបញ្ចប់កិច្ចសន្យារួចហើយ!');
            }
            if ($employee->hired_date && $request->last_working_date < $employee->hired_date) {
                throw new \Exception('ថ្ងៃធ្វើការចុងក្រោយ មិនអាចមុនថ្ងៃចូលធ្វើការបានទេ!');
            }

            $employee->update([
                'terminated_date' => $request->terminated_date,
                'last_working_date' => $request->last_working_date,
                'termination_type' => $request->termination_type,
                'termination_reason' => $request->termination_reason,
                'is_active' => false,
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Successful Terminate Employee!',
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Correct the termination details of an employee who already left
     * (wrong date, type or reason). Terminating someone still uses terminate().
     */
    public function updateTermination(Request $request)
    {
        $request->validate([
            'terminated_date' => 'required|date',
            'last_working_date' => 'required|date|before_or_equal:terminated_date',
            'termination_type' => 'required|in:resignation,dismissal,end_of_contract,retirement,mutual_agreement,other',
            'termination_reason' => 'nullable|string|max:1000',
        ]);

        try {
            $employee = Employee::findOrFail($request->id);

            if (!$employee->terminated_date && $employee->is_active) {
                throw new \Exception('បុគ្គលិកនេះមិនទាន់បញ្ចប់កិច្ចសន្យានៅឡើយទេ!');
            }
            if ($employee->hired_date && $request->last_working_date < $employee->hired_date) {
                throw new \Exception('ថ្ងៃធ្វើការចុងក្រោយ មិនអាចមុនថ្ងៃចូលធ្វើការបានទេ!');
            }

            $employee->update([
                'terminated_date' => $request->terminated_date,
                'last_working_date' => $request->last_working_date,
                'termination_type' => $request->termination_type,
                'termination_reason' => $request->termination_reason,
                'is_active' => false,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Successful Update Termination!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function dashboardSummary(Request $request)
    {
        try {
            $branch = $this->getBranch();
            $today = Carbon::today();

            $newEmployeeCount = Employee::query()
                ->whereBranch($branch)
                ->whereBetween('hired_date', [
                    $today->copy()->startOfMonth()->format('Y-m-d'),
                    $today->copy()->endOfMonth()->format('Y-m-d'),
                ])
                ->count();

            $totalEmployeeCount = Employee::query()
                ->whereBranch($branch)
                ->where('is_active', true)
                ->count();

            return response()->json([
                'status' => true,
                'data' => [
                    'new_employee_count' => $newEmployeeCount,
                    'total_employee_count' => $totalEmployeeCount,
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function monthlyGrowth(Request $request)
    {
        try {
            $branch = $this->getBranch();
            $labels = [];
            $counts = [];

            for ($i = 5; $i >= 0; $i--) {
                $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

                $count = Employee::query()
                    ->whereBranch($branch)
                    ->where('hired_date', '<=', $monthEnd->format('Y-m-d'))
                    ->where(function ($query) use ($monthEnd) {
                        $query->whereNull('terminated_date')
                            ->orWhere('terminated_date', '>', $monthEnd->format('Y-m-d'));
                    })
                    ->count();

                $labels[] = ['month' => (int)$monthEnd->format('n'), 'year' => (int)$monthEnd->format('Y')];
                $counts[] = $count;
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'labels' => $labels,
                    'counts' => $counts,
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function countByPosition(Request $request)
    {
        try {
            $rows = Employee::query()
                ->join('positions', 'positions.id', '=', 'employees.position_id')
                ->whereBranch($this->getBranch())
                ->where('employees.is_active', true)
                ->groupBy('positions.id', 'positions.name_kh', 'positions.name_en', 'employees.gender')
                ->selectRaw('positions.id as position_id, positions.name_kh, positions.name_en, employees.gender, count(employees.id) as total')
                ->get();

            $positions = $rows->groupBy('position_id')
                ->map(function ($group) {
                    $male = (int)(optional($group->firstWhere('gender', 'male'))->total ?? 0);
                    $female = (int)(optional($group->firstWhere('gender', 'female'))->total ?? 0);

                    return [
                        'name_kh' => $group->first()->name_kh,
                        'male' => $male,
                        'female' => $female,
                        'total' => $male + $female,
                    ];
                })
                ->sortByDesc('total')
                ->values();

            return response()->json([
                'status' => true,
                'data' => [
                    'labels' => $positions->pluck('name_kh'),
                    'male_counts' => $positions->pluck('male'),
                    'female_counts' => $positions->pluck('female'),
                    'total_counts' => $positions->pluck('total'),
                ]
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


}
