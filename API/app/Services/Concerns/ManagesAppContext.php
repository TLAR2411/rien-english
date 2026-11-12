<?php

namespace App\Services\Concerns;

// <-- Note the namespace

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

trait ManagesAppContext
{
    /**
     * Get the current user.
     */
    public function getUser()
    {
        return Auth::user();
    }

    /**
     * Get the Branch ID from the header.
     */
    public function getBranch()
    {
        return request()->header('X-Branch-Id', '*');
    }

    /**
     * Get the Branch Abbr from the header.
     */
    public function getBranchAbbr()
    {
        return request()->header('X-Branch-Abbr', null);
    }

    public function storeImage(string $inputImage, string $imagePage): string
    {
        // ---- settings (tweak as you like) ----
        $targetW = 1024;           // max width
        $targetH = 1024;           // max height
        $cover = false;          // true = crop to exact WxH, false = fit inside box
        $quality = 80;             // webp quality 1..100
        $sizeCap = 8 * 1024 * 1024; // 8MB base64 payload cap (optional)

        // ---- decode & validate base64 ----
        if (! preg_match('/^data:image\/(\w+);base64,/', $inputImage, $type)) {
            abort(500, 'Invalid Base64 image format');
        }
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower($type[1]);
        if (! in_array($ext, $allowed, true)) {
            abort(500, 'Unsupported image type');
        }

        $b64 = substr($inputImage, strpos($inputImage, ',') + 1);
        if ($sizeCap && strlen($b64) > $sizeCap * 1.38) { // rough base64 factor
            abort(500, 'Image size exceeds limit');
        }
        $binary = base64_decode($b64, true);
        if ($binary === false) {
            abort(500, 'Invalid Base64 image data');
        }

        // ---- read & orient ----
        $img = Image::read($binary);
        if (method_exists($img, 'orient')) {
            $img = $img->orient();
        } elseif (method_exists($img, 'orientate')) {
            $img = $img->orientate();
        }

        // ---- resize ----
        if ($cover) {
            // exact WxH (crops)
            $img = $img->cover($targetW, $targetH);
        } else {
            // keep aspect, fit inside WxH (no crop)
            $img = $img->scaleDown(width: $targetW, height: $targetH);
        }

        // ---- encode to webp & store ----
        $now = Carbon::now();
        $filename = $now->year.'/'.$now->month.'/'.$now->day.'/'.Str::uuid().'.webp';
        $storagePath = 'images/'.$imagePage.'/'.$filename;

        $webp = $img->toWebp($quality);
        Storage::disk('public')->put($storagePath, (string) $webp);

        // optional: free memory
        if (method_exists($img, 'destroy')) {
            $img->destroy();
        }
        unset($binary, $webp);

        return $storagePath; // relative path on 'public' disk
    }
}
