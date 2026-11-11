<?php

namespace App\Http\Resources\Learning\Site;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lesson in a public list (landing page, lesson list): short fields only, no lesson text.
 *
 * @mixin \App\Models\Learning\Lesson
 */
class LessonCardResource extends JsonResource
{
    /** Columns the card needs (select only these). */
    public const COLUMNS = ['id', 'course_id', 'skill', 'title_en', 'title_km', 'slug', 'summary', 'sort_order', 'created_at'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'skill' => $this->skill,
            'title_en' => $this->title_en,
            'title_km' => $this->title_km,
            'summary' => $this->summary,
            'level' => $this->course?->level?->only(['id', 'code', 'name', 'name_km']),
            'examples_count' => $this->whenCounted('examples'),
            'has_quiz' => (bool) $this->quiz_exists,
        ];
    }
}
