<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lesson for admin screens. The list leaves out the long HTML columns
 * (they are only sent when they were selected).
 *
 * @mixin \App\Models\Learning\Lesson
 */
class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'skill' => $this->skill,
            'title_en' => $this->title_en,
            'title_km' => $this->title_km,
            'slug' => $this->slug,
            'summary' => $this->whenHas('summary'),
            'content' => $this->whenHas('content'),
            'content_km' => $this->whenHas('content_km'),
            'transcript' => $this->whenHas('transcript'),
            'checklist' => $this->whenHas('checklist'),
            'min_words' => $this->whenHas('min_words'),
            'max_words' => $this->whenHas('max_words'),
            'sample_answer' => $this->whenHas('sample_answer'),
            'audio_url' => $this->whenHas('audio_url'),
            'video_url' => $this->whenHas('video_url'),
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'examples_count' => $this->whenCounted('examples'),
            'updated_at' => $this->updated_at,
        ];
    }
}
