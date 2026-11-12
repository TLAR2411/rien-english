<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One finished matching game (words ↔ meanings) of a vocabulary lesson. */
class WordMatchAttempt extends Model
{
    protected $fillable = ['user_id', 'lesson_id', 'score', 'total', 'percent'];

    protected $casts = [
        'score' => 'integer',
        'total' => 'integer',
        'percent' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
