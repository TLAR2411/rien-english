<?php

namespace App\Models\Learning;

use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/** A CEFR level: A1, A2, B1, B2, C1. */
class Level extends Model
{
    use HasFactory, HasSortOrder;

    protected $fillable = ['code', 'name', 'name_km', 'sort_order'];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Course::class);
    }
}
