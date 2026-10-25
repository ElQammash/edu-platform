<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetPanelLocale;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelLocaleController extends Controller
{
    /**
     * Switch the panels' language and go back. Saved on the account (so it
     * follows the teacher across devices) and in the session (for the login
     * page, before anyone is signed in).
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');
        abort_unless(in_array($locale, $supported, true), 404);

        $request->session()->put(SetPanelLocale::SESSION_KEY, $locale);

        $user = Auth::user();
        if ($user instanceof User) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return redirect()->back(fallback: url('/'));
    }
}
