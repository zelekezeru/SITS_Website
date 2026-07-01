<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net",
            "img-src 'self' data: https://laravel.com",
            "connect-src 'self'",
            "worker-src 'self' blob:",
            "frame-ancestors 'none'",
        ];

        if (app()->environment('local')) {
            $directives = array_map(function ($directive) {
                if (str_starts_with($directive, 'script-src')) {
                    return $directive . " http://localhost:5173 http://127.0.0.1:5173";
                }
                if (str_starts_with($directive, 'style-src')) {
                    return $directive . " http://localhost:5173 http://127.0.0.1:5173";
                }
                if (str_starts_with($directive, 'connect-src')) {
                    return $directive . " ws://localhost:5173 ws://127.0.0.1:5173 http://localhost:5173 http://127.0.0.1:5173";
                }
                return $directive;
            }, $directives);
        }

        $response->headers->set('Content-Security-Policy', implode('; ', $directives) . ';');

        return $response;
    }
}
