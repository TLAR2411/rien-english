<?php

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

/** Move a row up or down in its list. */
class MoveRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'direction' => ['required', 'in:up,down'],
        ];
    }
}
