<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of the Filament panels: the signed-in user's saved choice, then
 * the session (chosen on the login page), then the app default. Filament's
 * Arabic translations switch the whole panel to RTL.
 */
class SetPanelLocale
{
    public const SESSION_KEY = 'panel_locale';

    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');

        $user = Auth::user();
        $candidates = [
            $user instanceof User ? $user->locale : null,
            $request->session()->get(self::SESSION_KEY),
        ];

        foreach ($candidates as $locale) {
            if (is_string($locale) && in_array($locale, $supported, true)) {
                App::setLocale($locale);
                break;
            }
        }

        return $next($request);
    }
}
