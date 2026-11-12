<?php

namespace App\Http\Requests\Learning;

use App\Enums\PublishStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Switch a row between draft and published. */
class StatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(PublishStatus::class)],
        ];
    }
}
