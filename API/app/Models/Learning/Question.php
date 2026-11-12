<?php

namespace App\Models\Learning;

use App\Enums\QuestionType;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory, HasSortOrder;

    protected $fillable = ['quiz_id', 'type', 'prompt', 'explanation', 'explanation_km', 'points', 'sort_order'];

    protected $casts = [
        'type' => QuestionType::class,
        'points' => 'integer',
    ];

    public function sortGroupColumn(): ?string
    {
        return 'quiz_id';
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->ordered();
    }
}
