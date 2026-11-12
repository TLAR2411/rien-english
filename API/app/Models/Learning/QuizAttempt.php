<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One time a student took a quiz. Graded on the server only. */
class QuizAttempt extends Model
{
    protected $fillable = ['user_id', 'quiz_id', 'score', 'total', 'percent', 'passed', 'started_at', 'finished_at'];

    protected $casts = [
        'score' => 'integer',
        'total' => 'integer',
        'percent' => 'integer',
        'passed' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
