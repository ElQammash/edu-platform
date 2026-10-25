<?php

namespace App\Filament\Shared;

use App\Http\Middleware\SetPanelLocale;
use Filament\Actions\Action;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\App;
use Illuminate\Support\HtmlString;

/**
 * Arabic/English for the Filament panels: applies the chosen language and
 * adds a switch to the user menu and the login page. Filament's Arabic
 * translations turn the layout RTL.
 */
class PanelLanguage
{
    private const NAMES = ['ar' => 'العربية', 'en' => 'English'];

    public static function configure(Panel $panel): Panel
    {
        return $panel
            // Cairo covers Arabic and Latin, so both languages look consistent.
            ->font('Cairo')
            ->middleware([SetPanelLocale::class])
            ->userMenuItems([
                Action::make('switch-language')
                    ->label(fn (): string => self::NAMES[self::other()])
                    ->icon(Heroicon::OutlinedLanguage)
                    ->url(fn (): string => route('panel.locale', self::other())),
            ])
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn (): HtmlString => new HtmlString(sprintf(
                '<p class="text-center text-sm"><a href="%s" class="font-medium text-primary-600 hover:underline" lang="%s">%s</a></p>',
                e(route('panel.locale', self::other())),
                e(self::other()),
                e(self::NAMES[self::other()]),
            )));
    }

    private static function other(): string
    {
        return App::getLocale() === 'ar' ? 'en' : 'ar';
    }
}
