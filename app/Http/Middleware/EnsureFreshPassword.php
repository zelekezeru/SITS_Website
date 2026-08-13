<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces a user who is still on their default/recovery password to change it
 * before reaching any other authenticated page. Exempts the force-change
 * routes themselves and logout, so the redirect loop has an exit.
 */
class EnsureFreshPassword
{
    private const EXEMPT_ROUTES = [
        'password.force-change',
        'password.force-change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if ($user && ! $user->password_changed && ! in_array($routeName, self::EXEMPT_ROUTES, true)) {
            // Remember where they were going so the change hands them straight
            // back. Matters most for the Moodle SSO hop: Moodle sends them to
            // /oauth/authorize, and without this they would change their
            // password only to land on the portal and have to start over.
            if ($request->isMethod('GET') && ! $request->expectsJson()) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
