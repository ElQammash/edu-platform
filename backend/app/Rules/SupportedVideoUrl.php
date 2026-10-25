<?php

namespace App\Rules;

use App\Support\VideoUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportedVideoUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! VideoUrl::isSupported($value)) {
            $fail('validation.custom.video_url.supported')->translate();
        }
    }
}
