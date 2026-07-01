<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * OIDC-style userinfo endpoint for the SITS identity provider.
 *
 * Moodle's core `auth_oauth2` plugin calls this (with the Bearer access token it
 * obtained from /oauth/token) to fetch the logged-in user's profile, then matches
 * or provisions the Moodle account by email. Exposes the standard OIDC claims plus
 * a `roles` array so Moodle (or a sync job) can map SITS roles → Moodle roles.
 */
class OidcUserInfoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $name  = trim((string) $user->name);
        $parts = preg_split('/\s+/', $name, 2) ?: [$name];
        $given  = $parts[0] ?? $name;
        $family = $parts[1] ?? $parts[0] ?? $name;

        return response()->json([
            'sub'                => (string) $user->id,
            'email'              => $user->email,
            'email_verified'     => $user->email_verified_at !== null,
            'name'               => $name,
            'given_name'         => $given,
            'family_name'        => $family,
            'preferred_username' => $user->email,
            'picture'            => $user->profile_image ? asset('storage/'.$user->profile_image) : null,
            // SITS roles (spatie) — Moodle maps/syncs these to its own roles.
            'roles'              => $user->getRoleNames()->values(),
        ]);
    }
}
