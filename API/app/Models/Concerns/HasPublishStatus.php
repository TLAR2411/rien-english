<?php

namespace App\Models\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Builder;

/** Rows with a draft/published `status`. Visitors and students only see published ones. */
trait HasPublishStatus
{
    public function initializeHasPublishStatus(): void
    {
        $this->mergeCasts(['status' => PublishStatus::class]);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), PublishStatus::Published);
    }

    public function isPublished(): bool
    {
        return $this->status === PublishStatus::Published;
    }
}
