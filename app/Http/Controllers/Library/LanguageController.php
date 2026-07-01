<?php

namespace App\Http\Controllers\Library;

use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    /**
     * Switch application language locale.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:en,am'],
        ]);

        session()->put('locale', $request->input('locale'));

        return back();
    }
}
