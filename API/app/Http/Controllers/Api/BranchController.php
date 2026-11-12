<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataTableResource;
use App\Models\Core\Branch;
use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    private function companyId(): ?int
    {
        return app(BranchContext::class)->companyId($this->getUser());
    }

    /**
     * All of the owner's branches, including disabled ones so they can be reopened.
     */
    private function ownBranches()
    {
        return Branch::query()->where('company_id', $this->companyId());
    }

    private function validateBranch(Request $request, ?int $id = null): void
    {
        $request->validate([
            'name_kh' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'abbr' => [
                'required', 'string', 'max:3',
                Rule::unique('branches', 'abbr')->where('company_id', $this->companyId())->ignore($id),
            ],
        ]);
    }

    public function all(Request $request)
    {
        try {
            $branches = Branch::query()
                ->whereBranch('*')
                ->where('is_active', true)->get();

            return response()->json([
                'status' => true,
                'data' => $branches,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $branches = $this->ownBranches()->findOrFail($request->id);

            return response()->json([
                'status' => true,
                'data' => $branches,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $branches = $this->ownBranches()->paginate($request->limit);
            $branches = DataTableResource::collection($branches)->response()->getData(true);

            return response()->json([
                'status' => true,
                'data' => $branches,
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
        $this->validateBranch($request);

        try {
            $branch = new Branch($request->except(['is_head', 'is_active']));
            $branch->company_id = $this->companyId();
            $branch->start_date = Carbon::parse($request->start_date)->format('Y-m-d');
            $branch->save();

            return response()->json([
                'status' => true,
                'message' => 'Successfully created branch!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        $this->validateBranch($request, $request->integer('id'));

        try {
            $branch = $this->ownBranches()->findOrFail($request->id);
            $branch->name_kh = $request->name_kh;
            $branch->name_en = $request->name_en;
            $branch->abbr = $request->abbr;
            $branch->contact = $request->contact;
            $branch->house_no = $request->house_no;
            $branch->street = $request->street;
            $branch->village_code = $request->village_code;
            $branch->region = $request->region;
            // App check-in: branch position and allowed distance
            $branch->latitude = $request->latitude ?: null;
            $branch->longitude = $request->longitude ?: null;
            $branch->check_in_radius_m = $request->check_in_radius_m ?: 150;
            $branch->require_check_in_area = $request->boolean('require_check_in_area', true);
            $branch->save();

            return response()->json([
                'status' => true,
                'message' => 'Successfully updated branch!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        try {
            $this->ownBranches()->findOrFail($request->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Successfully deleted branch!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function disable(Request $request)
    {
        try {
            $branch = $this->ownBranches()->findOrFail($request->id);
            $branch->is_active = $branch->is_active == true ? false : true;
            $branch->save();

            return response()->json([
                'status' => true,
                'message' => $branch->is_active == 1 ? 'Successfully open branch!' : 'Successfully disable branch!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
