<?php

namespace App\Http\Requests\Learning;

use App\Enums\PublishStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Paid courses (is_free / price) are Phase 9, so they are not editable yet. */
class CourseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'title' => ['required', 'string', 'max:255'],
            'title_km' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('courses')->ignore($this->route('course'))],
            'status' => ['required', Rule::enum(PublishStatus::class)],
        ];
    }
}
