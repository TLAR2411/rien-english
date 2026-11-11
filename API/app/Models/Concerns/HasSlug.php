<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills `slug` (the URL name, e.g. "present-simple") from slugSource() when it is empty,
 * and keeps it unique: "present-simple", "present-simple-2", …
 */
trait HasSlug
{
    abstract protected function slugSource(): ?string;

    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $model->slug = $model->uniqueSlug(Str::slug($model->slug ?: $model->slugSource()) ?: 'item');
        });
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;

        for ($i = 2; $this->slugTaken($slug); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    private function slugTaken(string $slug): bool
    {
        return static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists();
    }
}
