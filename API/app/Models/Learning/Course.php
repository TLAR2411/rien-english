<?php

namespace App\Models\Learning;

use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A course inside a level, e.g. "Grammar A1". */
class Course extends Model
{
    use HasFactory, HasPublishStatus, HasSlug, HasSortOrder;

    protected $fillable = [
        'level_id', 'title', 'title_km', 'description', 'slug',
        'is_free', 'price', 'status', 'sort_order',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function sortGroupColumn(): ?string
    {
        return 'level_id';
    }

    protected function slugSource(): ?string
    {
        return $this->title;
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
