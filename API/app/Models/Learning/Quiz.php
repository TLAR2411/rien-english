<?php

namespace App\Models\Learning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** The quiz of a lesson (one per lesson). */
class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['lesson_id', 'title', 'pass_score', 'time_limit_minutes'];

    protected $casts = [
        'pass_score' => 'integer',
        'time_limit_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Quiz $quiz) {
            $quiz->pass_score ??= config('learning.quiz.pass_score');
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->ordered();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
