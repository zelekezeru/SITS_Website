<?php

namespace App\Support;

use App\Models\User;

/**
 * Single source of truth for the cross-app portal switcher — the list of sites
 * a user may jump to from the avatar menu in every layout.
 *
 * Before this class each layout (website, ERP/AdminLayout, Library, Website
 * Admin) re-implemented its own `hasErpAccess` / `hasLibraryAccess` /
 * `isWebsiteAdmin` computed props with slightly different allow-lists, so the
 * same account saw a different set of portals depending on which app it was
 * standing in. The rules now live here once, are evaluated server-side against
 * the real gates, and are shared as the `portals` Inertia prop.
 *
 * Each gate deliberately mirrors the guard on the destination route so the menu
 * can never offer a link that 403s:
 *   - Website Admin  → `role:SUPERADMIN|ADMIN|EDITOR` (routes/web.php)
 *   - Integrity      → the `access-integrity-suite` Gate (AppServiceProvider)
 *   - ERP / Library  → `auth` only; scoped by role below to keep the menu honest
 *
 * @see \App\Http\Middleware\HandleInertiaRequests::share()
 * @see resources/js/Components/PortalSwitcher.vue
 */
class PortalDirectory
{
    /** Roles allowed into the Website Admin console — mirrors its route middleware. */
    private const WEBSITE_ADMIN_ROLES = ['superadmin', 'admin', 'editor'];

    /**
     * The portals a user may switch to, in menu order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $roles = self::roles($user);
        $entries = [];

        // ── SITS ERP ─────────────────────────────────────────────────────────
        // Every staff account. Students are the only accounts with no business
        // in the ERP, so they are the only ones excluded.
        if ($roles !== [] && ! in_array('student', $roles, true)) {
            $entries[] = self::entry(
                key: 'erp',
                label: 'SITS ERP',
                href: route('dashboard'),
                icon: 'ShieldCheck',
                description: 'HR, payroll, attendance and evaluation',
            );
        }

        // ── Digital Library (ILS) ────────────────────────────────────────────
        // Auth-only route, and a library is for everyone who can log in. Always
        // lands on the ILS dashboard — NOT the JSTOR gateway, which is separate.
        $entries[] = self::entry(
            key: 'library',
            label: 'Digital Library',
            href: route('library.dashboard'),
            icon: 'LibraryBig',
            description: 'Catalog, loans, holds and reading room',
        );

        // The external research databases (JSTOR, EBSCO) are deliberately NOT
        // listed here — they live on the Library dashboard and topbar, where
        // they belong, rather than cluttering the cross-app switcher.

        // ── Academic Integrity Suite ─────────────────────────────────────────
        if ($user->can('access-integrity-suite')) {
            $entries[] = self::entry(
                key: 'integrity',
                label: 'Academic Integrity',
                href: route('integrity.dashboard'),
                icon: 'ShieldAlert',
                description: 'Plagiarism checks and writing tools',
            );
        }

        // ── LMS (Moodle) ─────────────────────────────────────────────────────
        // Students and trainers get the SSO hand-off; everyone else gets the
        // plain Moodle URL, because the SSO bridge only provisions those two.
        $ssoUser = array_intersect(['student', 'trainer'], $roles) !== [];
        $entries[] = self::entry(
            key: 'lms',
            label: self::lmsLabel($roles),
            href: $ssoUser ? route('lms.redirect') : config('services.moodle.url', 'https://lms.sits.edu.et'),
            icon: 'GraduationCap',
            description: 'Courses, assignments and grades',
            external: true,
            target: '_blank',
        );

        // ── Website Admin console ────────────────────────────────────────────
        if (array_intersect(self::WEBSITE_ADMIN_ROLES, $roles) !== []) {
            $entries[] = self::entry(
                key: 'website-admin',
                label: 'Website Admin',
                href: route('website.admin.dashboard'),
                icon: 'Globe',
                description: 'Public site content and media',
            );
        }

        return $entries;
    }

    /**
     * The user's role names, lower-cased.
     *
     * Role names are inconsistent across the three merged apps ('SUPERADMIN',
     * 'President / Super Admin', 'Trainer'…), so every comparison here is
     * case-insensitive — matching what the layouts did before.
     *
     * @return array<int, string>
     */
    private static function roles(User $user): array
    {
        return $user->getRoleNames()
            ->map(fn (string $role) => mb_strtolower($role))
            ->values()
            ->all();
    }

    /** @param array<int, string> $roles */
    private static function lmsLabel(array $roles): string
    {
        return match (true) {
            in_array('student', $roles, true) => 'Student Portal',
            in_array('trainer', $roles, true) => 'Instructor Portal',
            default => 'SITS LMS',
        };
    }

    /** @return array<string, mixed> */
    private static function entry(
        string $key,
        string $label,
        string $href,
        string $icon,
        string $description,
        bool $external = false,
        ?string $target = null,
    ): array {
        return compact('key', 'label', 'href', 'icon', 'description', 'external', 'target');
    }
}
