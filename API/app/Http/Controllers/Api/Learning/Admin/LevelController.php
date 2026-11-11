<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Requests\Learning\LevelRequest;
use App\Http\Requests\Learning\MoveRequest;
use App\Http\Resources\Learning\LevelResource;
use App\Models\Learning\Level;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LevelController extends AdminController
{
    public const PERMISSION_GROUP = 'levels';

    public function index(Request $request): JsonResponse
    {
        $levels = Level::query()
            ->withCount('courses')
            ->when($this->filter($request, 'search'), fn ($q, $search) => $q->where(
                fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")
            ))
            ->ordered()
            ->paginate($this->perPage($request));

        return $this->paginated(LevelResource::collection($levels));
    }

    /** Every level, for select boxes. */
    public function all(): JsonResponse
    {
        return $this->ok(LevelResource::collection(Level::ordered()->get()));
    }

    public function store(LevelRequest $request): JsonResponse
    {
        return $this->created(new LevelResource(Level::create($request->validated())));
    }

    public function update(LevelRequest $request, Level $level): JsonResponse
    {
        $level->update($request->validated());

        return $this->ok(new LevelResource($level), 'Saved.');
    }

    public function move(MoveRequest $request, Level $level): JsonResponse
    {
        $level->move($request->validated('direction'));

        return $this->ok();
    }

    public function destroy(Level $level): JsonResponse
    {
        abort_if($level->courses()->exists(), 422, 'This level still has courses. Move or delete them first.');

        $level->delete();

        return $this->ok(message: 'Deleted.');
    }
}
