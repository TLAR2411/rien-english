<?php

namespace App\Models\Learning;

use App\Enums\WritingStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's writing for a lesson, reviewed by a teacher. */
class WritingSubmission extends Model
{
    protected $fillable = ['user_id', 'lesson_id', 'text', 'status', 'score', 'feedback', 'reviewed_by', 'reviewed_at'];

    protected $casts = [
        'status' => WritingStatus::class,
        'score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
