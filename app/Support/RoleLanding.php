<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth mapping a role to its landing route.
 *
 * The map is ordered by seniority: a user holding several roles lands on the
 * most senior one's page. Users with no role (or none matching) fall back to
 * the employee self-service dashboard.
 */
class RoleLanding
{
    /** @var array<string, string> role name => route name, most senior first */
    public const MAP = [
        'President / Super Admin' => 'admin.dashboard',
        'Vice President' => 'executive.dashboard',
        'Dean of the Seminary' => 'dean.dashboard',
        'Operational Manager' => 'operations.dashboard',
        'Finance Officer' => 'finance.dashboard',
        'Department Head' => 'department.dashboard',
        'Registrar' => 'registrar.dashboard',
        'Employee' => 'dashboard',
    ];

    // Public-website users (and any account with no ERP role) land on the
    // unified portal hub, from which they can launch the ERP, LMS and Library.
    public const FALLBACK = 'portal';

    /** The landing route name for a user, by their most senior role. */
    public static function routeName(?User $user): string
    {
        if ($user) {
            $roles = $user->getRoleNames();

            foreach (self::MAP as $role => $route) {
                if ($roles->contains($role)) {
                    return $route;
                }
            }
        }

        return self::FALLBACK;
    }

    /** The absolute landing URL for a user. */
    public static function url(?User $user): string
    {
        return route(self::routeName($user));
    }

    /** The role name whose landing the user resolves to (or null). */
    public static function resolvedRole(?User $user): ?string
    {
        if ($user) {
            $roles = $user->getRoleNames();

            foreach (array_keys(self::MAP) as $role) {
                if ($roles->contains($role)) {
                    return $role;
                }
            }
        }

        return null;
    }
}
