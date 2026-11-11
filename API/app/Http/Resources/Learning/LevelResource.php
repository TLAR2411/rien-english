<?php

namespace App\Http\Resources\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Learning\Level */
class LevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'name_km' => $this->name_km,
            'sort_order' => $this->sort_order,
            'courses_count' => $this->whenCounted('courses'),
        ];
    }
}
