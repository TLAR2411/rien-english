<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

/** One file: its type and size limits come from config/learning.php → uploads.types. */
class UploadRequest extends FormRequest
{
    public function rules(): array
    {
        $types = config('learning.uploads.types');
        $limits = $types[$this->input('type')] ?? null;

        return [
            'type' => ['required', 'in:'.implode(',', array_keys($types))],
            'file' => $limits
                ? ['required', 'file', 'mimes:'.implode(',', $limits['mimes']), 'max:'.$limits['max_kb']]
                : ['required', 'file'],
        ];
    }
}
