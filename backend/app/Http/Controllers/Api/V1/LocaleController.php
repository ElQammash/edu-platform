<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class LocaleController extends Controller
{
    /**
     * PUT /me/locale: remember the interface language on the account, so
     * emails (live lesson reminders) arrive in it.
     */
    public function update(Request $request): Response
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(config('app.supported_locales'))],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill(['locale' => $data['locale']])->save();

        return response()->noContent();
    }
}
