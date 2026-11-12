<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageController extends Controller
{
    public function resize(Request $r)
    {
        $url = $r->query('url');
        $w = (int) $r->query('w', 72);
        $h = (int) $r->query('h', 72);
        $q = (int) $r->query('q', 60);
        $fmt = $r->query('fmt', 'webp');
        $fit = $r->query('fit', 'inside'); // Get the fit instruction

        $path = parse_url($url, PHP_URL_PATH);
        $path = Str::after($path, '/storage/');
        abort_unless(Storage::disk('public')->exists($path), 404);

        $ext = strtolower($fmt);
        // Include $fit in the cache key so 'cover' and 'inside' versions don't conflict
        $cacheKey = "thumbs/{$fit}_{$w}x{$h}/".pathinfo($path, PATHINFO_FILENAME).".{$ext}";

        if (! Storage::disk('public')->exists($cacheKey)) {
            $img = Image::read(Storage::disk('public')->get($path));

            // --- CHANGE THIS SECTION ---
            if ($fit === 'cover') {
                $img->cover($w, $h); // For square avatars
            } elseif ($fit === 'cover-top') {
                // Avatars of people: a tall photo (e.g. 4x6) is cut from the
                // bottom so the face stays in; other shapes crop as 'cover'
                $img->cover($w, $h, 'top');
            } else {
                // This is "Zoom to fit" - it fits the whole image inside the WxH box
                $img->scaleDown(width: $w, height: $h);
            }
            // ---------------------------

            $binary = match ($ext) {
                'avif' => $img->toAvif($q),
                'jpeg', 'jpg' => $img->toJpeg($q),
                default => $img->toWebp($q),
            };
            Storage::disk('public')->put($cacheKey, (string) $binary);
        }

        $data = Storage::disk('public')->get($cacheKey);

        return response($data, 200)
            ->header('Content-Type', match ($ext) {
                'avif' => 'image/avif', 'jpeg','jpg' => 'image/jpeg', default => 'image/webp'
            })
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }
}
