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
        'content', 'content_km', 'transcript', 'audio_url', 'video_url', 'sort_order', 'status',
        // Writing lessons (T33)
        'checklist', 'min_words', 'max_words', 'sample_answer',
    ];

    protected $casts = [
        'skill' => LessonSkill::class,
        'audio_url' => MediaUrl::class,
        // Lesson HTML is cleaned when saved (rules: config/purify.php → lesson)
        'content' => PurifyHtmlOnSet::class.':lesson',
        'content_km' => PurifyHtmlOnSet::class.':lesson',
        'transcript' => PurifyHtmlOnSet::class.':lesson',
        'sample_answer' => PurifyHtmlOnSet::class.':lesson',
        'checklist' => 'array',
        'min_words' => 'integer',
        'max_words' => 'integer',
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

    /** Can visitors and students see this lesson? (see scopeVisible) */
    public function isVisible(): bool
    {
        return static::visible()->whereKey($this->id)->exists();
    }

    /** A published writing lesson: students may send their writing for it. */
    public function acceptsWriting(): bool
    {
        return $this->skill === LessonSkill::Writing && $this->isVisible();
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

    /** Writing lessons: what students sent (T33). */
    public function writingSubmissions(): HasMany
    {
        return $this->hasMany(WritingSubmission::class);
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
