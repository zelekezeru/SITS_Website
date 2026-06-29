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
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
