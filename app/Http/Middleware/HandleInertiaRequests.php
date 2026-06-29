<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $isPresident = $user && $user->hasRole('President / Super Admin');

        // Role-aware portal shell: sidebar, brand subtitle and topbar alerts.
        $context = \App\Support\PortalContext::for($user);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                ] : null,
                'pending_users' => $isPresident
                    ? \App\Models\User::where('is_approved', false)->get(['id', 'name', 'email'])
                    : [],
            ],
            'notifications' => $context['notifications'],
            'fiscalYear' => $isPresident ? \App\Support\FiscalYear::payload() : null,
            'nav' => $context['nav'],
            'portal' => $context['portal'],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            // Bilingual (English / Amharic). The active locale + its UI strings are
            // shared so the frontend can translate without an extra round-trip.
            'locale' => app()->getLocale(),
            'translations' => (array) trans('app'),
        ];
    }
}
