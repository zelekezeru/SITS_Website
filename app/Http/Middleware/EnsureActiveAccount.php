<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces the login gate for the whole authenticated session, not just at
 * sign-in: if an account is unapproved or deactivated mid-session, the next
 * request logs them out. Keeps "approved AND active" continuously true.
 */
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canLogin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is no longer active. Please contact an administrator.',
            ]);
        }

        return $next($request);
    }
}
