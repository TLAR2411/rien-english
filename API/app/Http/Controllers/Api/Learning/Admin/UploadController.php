<?php

namespace App\Http\Controllers\Api\Learning\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\UploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/** Saves an uploaded audio or image file and returns its URL. */
class UploadController extends Controller
{
    use ApiResponses;

    public function __invoke(UploadRequest $request): JsonResponse
    {
        $type = $request->validated('type');
        $disk = config('learning.uploads.disk');
        $folder = config('learning.uploads.folder')."/{$type}/".now()->format('Y/m');

        $path = $request->file('file')->store($folder, $disk);

        return $this->ok(['path' => $path, 'url' => Storage::disk($disk)->url($path)], code: 201);
    }
}
