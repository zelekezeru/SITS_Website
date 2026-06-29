<?php

use App\Support\RoleLanding;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ERP needs locale handling + Inertia shared props on every web request.
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            // ERP role/account gates
            'role.landing' => \App\Http\Middleware\EnsureRole::class,
            'active' => \App\Http\Middleware\EnsureActiveAccount::class,
            'password.fresh' => \App\Http\Middleware\EnsureFreshPassword::class,
            // Spatie permission middleware (used by both website and ERP routes)
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        // Guests hitting auth-only pages -> login. Authenticated users hitting
        // guest-only pages (login/register) -> their role-based landing.
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(
            fn ($request) => RoleLanding::url($request->user())
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render a clean 403 page when a user lacks the required role/permission.
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access Denied. You do not have the required role.'], 403);
            }
            return response()->view('errors.403', [], 403);
        });
    })->create();
