# SITS ↔ Moodle Integration Runbook

Goal: integrate **Moodle 5.2** (latest; AI subsystem) at `lms.sits.edu.et` with the SITS
website (`sits.edu.et`, Laravel 12) so it behaves like one system — single account,
role-based access, branded templates, portal embedding, and a clean migration of the
**existing** Moodle's data.

Decisions (locked 2026-06-30):
- Existing Moodle present → **migrate** its data (path TBD once version known).
- Hosting: **direct server access** to `lms.sits.edu.et`.
- SSO: **OAuth2/OIDC**, SITS app = identity provider.
- Version: **install 5.2 now → minor-upgrade to 5.3 LTS** when it ships (Oct 2026).

---

## Architecture

```
                 ┌─────────────────────────────┐
 Browser ───────▶│  SITS app  (sits.edu.et)     │   Laravel 12
                 │  - Auth (Breeze) + Passport  │◀── OIDC IdP (/oauth/*)
                 │  - Roles (spatie)            │
                 │  - Portal hub (/portal)      │── Web Services client (MoodleService)
                 └──────────────┬──────────────┘
                                │ OIDC login + REST sync
                                ▼
                 ┌─────────────────────────────┐
                 │  Moodle 5.2 (lms.sits.edu.et)│   PHP 8.2+, own MariaDB DB
                 │  - auth: oauth2 (→ SITS)     │
                 │  - role map, enrolments      │
                 │  - SITS Boost child theme    │
                 │  - AI subsystem (Gemini/…)   │
                 └─────────────────────────────┘
```

One identity lives in the SITS app. Moodle delegates login to it (OIDC). User profile +
role + enrolment data is pushed/pulled via Moodle Web Services (the existing
`App\Services\MoodleService`, hardened).

---

## Phase 0 — Prerequisites (BLOCKING — need from you)

1. **Old Moodle version** (Site admin → Notifications, or `config.php` + `version.php`).
   Determines migration path:
   - Same major or one step behind → in-place **sequential upgrade**.
   - Several majors behind → staged upgrade through intermediate versions, **or** course
     **backup/restore (`.mbz`)** into a clean 5.2 (content fidelity preserved; site-wide
     settings/users handled separately).
2. **Server access** to `lms.sits.edu.et`: SSH host/user/key, sudo, web root, PHP version,
   web server (Apache/Nginx), and whether MariaDB/MySQL is on the same host.
3. **DNS/TLS**: confirm `lms.sits.edu.et` resolves to that server and has a valid cert.
4. **A dedicated MariaDB DB** for Moodle (e.g. `moodle`) — separate from `sits_unified`.
   Moodle requires its own database; it must NOT share tables with the SITS app.

> Until 1–2 are provided, only the SITS-side work (Phases 2–3 groundwork) can proceed.

---

## Phase 1 — Stand up Moodle 5.2

- Requirements: PHP 8.2+ (matches SITS), MariaDB 10.11+/MySQL 8, required PHP exts
  (`gd, intl, mbstring, soap, zip, curl, xmlrpc-equivalent`), cron, a `moodledata`
  directory **outside** the web root.
- Install latest 5.2 stable; create the empty `moodle` DB; run the web installer or CLI
  (`admin/cli/install.php`).
- Set `$CFG->wwwroot = 'https://lms.sits.edu.et'`; lock down `moodledata` perms.
- Configure **cron** (`admin/cli/cron.php` every minute) — required for sync, AI, etc.
- Verify: Site admin → Notifications shows 5.2; `core_webservice_get_site_info` reachable.

## Phase 2 — SSO via OAuth2/OIDC (SITS = IdP)

SITS side (built here):
- Install **Laravel Passport**; issue an OAuth2 client for Moodle (auth-code + PKCE).
- Expose OIDC-ish endpoints: `/oauth/authorize`, `/oauth/token`, and a **`/oauth/userinfo`**
  returning `sub, email, name, given_name, family_name, roles[]`.
- Gate by approval/active flags already in `users` (is_approved/is_active).

Moodle side (server):
- Enable core **`auth_oauth2`**; add a custom OAuth2 issuer pointing at the SITS endpoints
  (authorization/token/userinfo URLs, client id/secret, scopes `openid email profile`).
- Enable "prevent account creation when authenticating" = OFF (auto-provision) but require
  email match; map `email` as the unique key.
- Result: "Log in with SITS" on Moodle → redirects to SITS → back to Moodle, logged in.
  Replaces the fragile `auth_userkey` SSO in `MoodleController` (kept as fallback only).

## Phase 3 — User + role/enrolment sync

- **Role map** (SITS spatie role → Moodle role / capability):
  | SITS role | Moodle role |
  |---|---|
  | STUDENT | student |
  | TRAINER | editingteacher |
  | STAFF / EDITOR | coursecreator |
  | LIBRARIAN | (custom: librarian) |
  | SUPERADMIN / ADMIN / President… | manager (or site admin for SUPERADMIN) |
- On OIDC login Moodle gets `roles[]`; a small Moodle **local plugin** (or the SITS-side
  `core_role_assign_roles` via Web Services) applies the system/role assignment.
- Scheduled SITS job (`php artisan moodle:sync`) reconciles profile + role + enrolment
  nightly via `MoodleService` (extend it with `core_role_assign_roles`,
  `enrol_manual_enrol_users`).

## Phase 4 — Branded theme + smart templates

- **Boost child theme** `theme_sits`: SITS palette (the dark slate / indigo of the public
  site), logo, fonts (Outfit / Plus Jakarta Sans), login page styled to match `sits.edu.et`.
- **Course templates**: pre-built course shells (e.g. "Standard Module", "Cohort Program")
  exported as `.mbz` and offered on course creation; optionally the *Templates* admin tool.
- "Configurable" = settings in the theme + a course-template picker, not hard-coded.

## Phase 5 — Portal embedding + AI subsystem

- SITS `/portal` shows the user's Moodle courses + progress (cards) via
  `MoodleService::getUserCourses()` (already scaffolded; harden + cache).
- Deep-link straight into a course from the portal (OIDC keeps it seamless).
- **AI subsystem**: Site admin → AI → add a provider instance (Gemini / OpenAI / Bedrock),
  assign lightweight model to summarise, stronger model to generate; AI output auto-tagged.
  Requires the chosen provider's API key (provider cost is separate; Moodle itself is free).

## Phase 6 — Migrate old Moodle data (no quality loss)

Decision tree (pick once old version known):
- **In-place upgrade** (preferred if ≤ a couple majors behind): clone old site to a staging
  copy, run the official sequential upgrade to 5.2, verify, then cut over. Preserves users,
  courses, grades, activity history exactly.
- **Backup/restore** (if far behind or DB unhealthy): per-course `.mbz` backups from the old
  site restored into a clean 5.2. Preserves course content/structure/grades; users + global
  config re-created/mapped. Use `admin/cli/backup.php` + restore, or the Bulk Course Tools.
- **Always**: take full DB + `moodledata` backups before touching anything; do it on a
  staging clone first; verify a sample of courses/grades/users before production cutover.

---

## What runs where

| Component | Location | Who builds |
|---|---|---|
| Passport OIDC IdP, `/oauth/userinfo`, role claim | SITS Laravel | Claude (here) |
| `MoodleService` hardening, `moodle:sync` job, portal cards | SITS Laravel | Claude (here) |
| Moodle install, `auth_oauth2` issuer, cron, TLS | Moodle server | Claude (with access) |
| `theme_sits` child theme, course templates | Moodle server | Claude (with access) |
| AI provider config + key | Moodle admin | You + Claude |
| Data migration execution | Moodle server (staging→prod) | Claude (with access) + your sign-off |

## Rollback / safety

- Every server step on a **staging clone** first; full backups before cutover.
- OIDC change is reversible (Moodle keeps manual accounts; `auth_userkey` fallback remains).
- No SITS production DB (`sits_unified`) changes for Moodle — Moodle uses its own DB.
