<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Persist the user's preferred UI language in the session.
     * The SetLocale middleware reads this on every subsequent request.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|in:' . implode(',', array_keys(config('app.available_locales', ['en' => 'English']))),
        ]);

        $request->session()->put('locale', $validated['locale']);

        return back();
    }
}
