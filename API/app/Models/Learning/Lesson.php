<?php

namespace App\Models\Learning;

use App\Casts\MediaUrl;
use App\Enums\LessonSkill;
use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stevebauman\Purify\Casts\PurifyHtmlOnSet;

/** One lesson of a course, e.g. "Present Simple" (skill: grammar). */
class Lesson extends Model
{
    use HasFactory, HasPublishStatus, HasSlug, HasSortOrder;

    protected $fillable = [
        'course_id', 'skill', 'title_en', 'title_km', 'slug', 'summary',
        'content', 'content_km', 'audio_url', 'sort_order', 'status',
    ];

    protected $casts = [
        'skill' => LessonSkill::class,
        'audio_url' => MediaUrl::class,
        // Lesson HTML is cleaned when saved (rules: config/purify.php → lesson)
        'content' => PurifyHtmlOnSet::class.':lesson',
        'content_km' => PurifyHtmlOnSet::class.':lesson',
    ];

    public function sortGroupColumn(): ?string
    {
        return 'course_id';
    }

    protected function slugSource(): ?string
    {
        return $this->title_en;
    }

    /** Lessons visitors and students may see: published, inside a published course. */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->published()->whereHas('course', fn (Builder $course) => $course->published());
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function examples(): HasMany
    {
        return $this->hasMany(LessonExample::class)->ordered();
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }
}
