<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A link to an uploaded file.
 * - Saved as the short path on the uploads disk ("learning/audio/2026/11/x.mp3"),
 *   so moving the site to another domain does not break old links.
 * - Read as a full URL. Links to other websites are kept as they are.
 */
class MediaUrl implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! $value || Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return self::disk()->url($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (! $value) {
            return null;
        }

        $base = rtrim(self::disk()->url(''), '/').'/';

        return Str::startsWith($value, $base) ? Str::after($value, $base) : $value;
    }

    private static function disk()
    {
        return Storage::disk(config('learning.uploads.disk'));
    }
}
