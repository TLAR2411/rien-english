<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A lesson a student has finished. */
class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = ['user_id', 'lesson_id', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
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
