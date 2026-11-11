<?php

namespace App\Http\Resources\Learning\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A lesson as students see it (public lesson page, student system, admin preview).
 *
 * @mixin \App\Models\Learning\Lesson
 */
class StudentLessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'skill' => $this->skill,
            'title_en' => $this->title_en,
            'title_km' => $this->title_km,
            'summary' => $this->summary,
            'content' => $this->content,
            'content_km' => $this->content_km,
            'audio_url' => $this->audio_url,
            // Only the video ID: the page plays it with YouTube's player
            'youtube_id' => $this->youtubeId(),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'slug' => $this->course->slug,
                'title' => $this->course->title,
                'title_km' => $this->course->title_km,
                'level' => $this->course->relationLoaded('level') ? $this->course->level->only(['id', 'code', 'name', 'name_km']) : null,
            ]),
            'examples' => $this->whenLoaded('examples', fn () => $this->examples->map->only(['id', 'sentence', 'translation_km', 'audio_url'])),
            'quiz' => new StudentQuizResource($this->whenLoaded('quiz')),
        ];
    }
}
