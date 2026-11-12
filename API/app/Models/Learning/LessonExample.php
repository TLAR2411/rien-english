<?php

namespace App\Models\Learning;

use App\Casts\MediaUrl;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An example sentence of a lesson, with Khmer translation and audio. */
class LessonExample extends Model
{
    use HasFactory, HasSortOrder;

    protected $fillable = ['lesson_id', 'sentence', 'translation_km', 'audio_url', 'sort_order'];

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
