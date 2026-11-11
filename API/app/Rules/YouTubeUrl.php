<?php

namespace App\Rules;

use App\Support\YouTube;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** The value must be a link to one YouTube video. */
class YouTubeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || YouTube::videoId($value) === null) {
            $fail('Please paste a link to a YouTube video, e.g. https://www.youtube.com/watch?v=…');
        }
    }
}
