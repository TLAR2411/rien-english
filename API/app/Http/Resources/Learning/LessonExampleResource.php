<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Learning\LessonExample */
class LessonExampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lesson_id' => $this->lesson_id,
            'sentence' => $this->sentence,
            'translation_km' => $this->translation_km,
            'audio_url' => $this->audio_url,
            'sort_order' => $this->sort_order,
        ];
    }
}
