<?php

namespace App\Models\Learning;

use App\Casts\MediaUrl;
use App\Enums\LessonSkill;
use App\Models\Concerns\HasPublishStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stevebauman\Purify\Casts\PurifyHtmlOnSet;

/** One lesson of a course, e.g. "Present Simple" (skill: grammar). */
class Lesson extends Model
{
    use HasFactory, HasPublishStatus, HasSlug, HasSortOrder;

    protected $fillable = [
        'course_id', 'skill', 'title_en', 'title_km', 'slug', 'summary',
        'content', 'content_km', 'audio_url', 'video_url', 'sort_order', 'status',
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

    /** Learning order: easiest level first (A1 before A2 …), then course, then lesson order. */
    public function scopeLearningOrder(Builder $query): Builder
    {
        return $query
            ->orderBy(
                Course::query()
                    ->join('levels', 'levels.id', '=', 'courses.level_id')
                    ->select('levels.sort_order')
                    ->whereColumn('courses.id', 'lessons.course_id')
            )
            ->orderBy('course_id')
            ->ordered();
    }

    /** The YouTube video ID from `video_url` (null when there is no video). */
    public function youtubeId(): ?string
    {
        return YouTube::videoId($this->video_url);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function examples(): HasMany
    {
        return $this->hasMany(LessonExample::class)->ordered();
    }

    /** New words ("Words to know" of a reading text, flashcards of a vocabulary lesson). */
    public function words(): HasMany
    {
        return $this->hasMany(LessonWord::class)->ordered();
    }

    /** Students who finished this lesson. */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /** Every try of this lesson's quiz, by any student. */
    public function quizAttempts(): HasManyThrough
    {
        return $this->hasManyThrough(QuizAttempt::class, Quiz::class);
    }
}
