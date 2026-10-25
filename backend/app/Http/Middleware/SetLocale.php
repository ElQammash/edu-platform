<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the locale the client asks for in Accept-Language (e.g. "ar" or
 * "ar-EG,ar;q=0.9,en;q=0.8"), so validation and API messages come back in
 * the user's language. Unsupported locales fall back to the app default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');

        $locale = $request->getPreferredLanguage($supported);

        if ($locale !== null && $request->hasHeader('Accept-Language')) {
            App::setLocale($locale);
        }

        $response = $next($request);
        $response->headers->set('Content-Language', App::getLocale());

        return $response;
    }
}
