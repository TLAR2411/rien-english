<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Learning\Course */
class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level_id' => $this->level_id,
            'level' => new LevelResource($this->whenLoaded('level')),
            'title' => $this->title,
            'title_km' => $this->title_km,
            'description' => $this->description,
            'slug' => $this->slug,
            'is_free' => $this->is_free,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'lessons_count' => $this->whenCounted('lessons'),
        ];
    }
}
