<?php

namespace App\Models\Learning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's access to a paid course (Phase 9 — not used yet). */
class Enrollment extends Model
{
    protected $fillable = ['user_id', 'course_id', 'amount_paid', 'paid_at'];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
