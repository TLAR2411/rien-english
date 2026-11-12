<?php

namespace App\Models\Learning;

use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An answer option. For typed questions (fill blank, word order) each correct
 * option is one accepted answer.
 *
 * `is_correct` is hidden from JSON on purpose: correct answers must never reach
 * the browser before the quiz is submitted. Admin screens add it back explicitly.
 */
class QuestionOption extends Model
{
    use HasFactory, HasSortOrder;

    protected $fillable = ['question_id', 'text', 'is_correct', 'sort_order'];

    protected $hidden = ['is_correct'];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function sortGroupColumn(): ?string
    {
        return 'question_id';
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
