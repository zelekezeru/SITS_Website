<?php

namespace App\Http\Middleware;

use App\Support\RoleLanding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards a role-specific landing route. Instead of a hard 403 on mismatch
 * (which is jarring under Inertia), the user is bounced to their *own*
 * landing page. The President / Super Admin may view any role's page.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->hasRole($role) || $user->hasRole('President / Super Admin')) {
            return $next($request);
        }

        return redirect(RoleLanding::url($user))
            ->with('error', "You don't have access to that area — here's your dashboard.");
    }
}
