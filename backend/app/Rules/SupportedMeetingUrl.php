<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * An https link to an allowed meeting service (config academy.meeting_hosts;
 * subdomains such as us02web.zoom.us are accepted).
 */
class SupportedMeetingUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isSupported($value)) {
            $fail('validation.custom.meeting_url.supported')->translate();
        }
    }

    public static function isSupported(string $url): bool
    {
        $parts = parse_url(trim($url));
        if ($parts === false || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])) {
            return false;
        }

        $host = strtolower($parts['host']);

        /** @var list<string> $allowed */
        $allowed = config('academy.meeting_hosts');

        foreach ($allowed as $domain) {
            if ($host === $domain || str_ends_with($host, '.'.$domain)) {
                return true;
            }
        }

        return false;
    }
}
