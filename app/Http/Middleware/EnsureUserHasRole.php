<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-gate middleware.
 *
 * Usage in routes:
 *   ->middleware('role:Librarian')
 *   ->middleware('role:Campus Admin,Librarian')   // OR logic — any of these
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        // Authenticated but lacks the required role → 403
        abort(403, 'You do not have permission to access this area.');
    }
}
