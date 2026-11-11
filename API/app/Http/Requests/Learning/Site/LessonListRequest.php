<?php

namespace App\Http\Requests\Learning\Site;

use App\Enums\LessonSkill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filters of the public lesson list: ?skill=grammar&level=A1&search=present&page=2 */
class LessonListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skill' => ['nullable', Rule::enum(LessonSkill::class)],
            'level' => ['nullable', 'string', 'max:10', Rule::exists('levels', 'code')],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
