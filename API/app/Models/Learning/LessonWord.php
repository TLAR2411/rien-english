<?php

namespace App\Models\Learning;

use App\Casts\MediaUrl;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A new word of a lesson, e.g. "rice field — វាលស្រែ", with its meaning, an example and audio. */
class LessonWord extends Model
{
    use HasFactory, HasSortOrder;

    protected $fillable = ['lesson_id', 'word', 'meaning_en', 'meaning_km', 'example', 'audio_url', 'sort_order'];

    protected $casts = [
        'audio_url' => MediaUrl::class,
    ];

    public function sortGroupColumn(): ?string
    {
        return 'lesson_id';
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
