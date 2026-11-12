<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Learning\LessonWord */
class LessonWordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'word' => $this->word,
            'meaning_en' => $this->meaning_en,
            'meaning_km' => $this->meaning_km,
            'example' => $this->example,
            'audio_url' => $this->audio_url,
            'sort_order' => $this->sort_order,
        ];
    }
}
