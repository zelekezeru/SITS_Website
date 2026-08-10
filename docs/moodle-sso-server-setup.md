# Moodle SSO — server setup (sits.edu.et → learn.sits.edu.et)

Goal: a user who is signed in at **sits.edu.et** clicks *eLearning → Moodle* and lands
inside **learn.sits.edu.et** already logged in. No second password prompt, ever.

Two independent mechanisms do that, and both should be configured — they cover
different entry points:

| Path | Entry point | Clicks after the website login | Needs |
|---|---|---|---|
| **A. auth_userkey** | `/go/lms` link on the website | **0** — lands logged in | plugin + web service token |
| **B. OIDC** | user starts at learn.sits.edu.et | **1** — press "Log in with SITS" | Passport client (already built) |

Path B alone cannot be zero-click: Moodle's `auth/oauth2/login.php` calls
`require_sesskey()`, so an external link cannot start the OAuth dance. That is why
A exists. B is still needed for anyone who bookmarks the LMS directly, and it is the
graceful fallback whenever the web service token is missing or Moodle's API is down.

Everything below runs **on the cPanel server**. Use the account's real PHP binary
(e.g. `ea-php83`). Take a database + `moodledata` backup before starting.

---

## 0. Prerequisites

- The Laravel site is deployed at `https://sits.edu.et` with `APP_URL=https://sits.edu.et`.
- Moodle is served at `https://learn.sits.edu.et` (see `docs/moodle-migration.md`).
- Both are HTTPS with valid certificates. OAuth2 and cookie SSO both break on mixed TLS.
- `php artisan passport:keys` has been run once on the server, and
  `storage/oauth-private.key` / `oauth-public.key` exist and are **not** world-readable:

```bash
cd ~/sits            # repo root on the server
ls -l storage/oauth-*.key || ea-php83 artisan passport:keys
chmod 600 storage/oauth-private.key storage/oauth-public.key
```

---

## Path B — OIDC (do this first; it is the safety net)

### B1. Issue the OAuth client on the SITS side

```bash
cd ~/sits
ea-php83 artisan moodle:oauth-client
```

It prints the client id, secret and the three endpoints. The command is idempotent —
re-run it any time to see the same credentials again. Add the printed id to `.env`:

```env
MOODLE_OAUTH_CLIENT_ID=<printed client id>
```

```bash
ea-php83 artisan config:cache
```

Setting that variable marks Moodle a **trusted** client, so Passport skips the consent
screen (`App\Models\OAuthClient::skipsAuthorization`). Without it SSO still works, but
each user sees an "Authorize" prompt on their first login.

### B2. Create the OAuth 2 service in Moodle

*Site administration → Server → OAuth 2 services → Create new **custom** service.*

Use **custom**, not "OpenID Connect": Passport does not issue an `id_token`, and the
OIDC service type expects one. The custom type reads the profile from the userinfo
endpoint, which is exactly what we serve.

| Field | Value |
|---|---|
| Name | `SITS` |
| Client ID | from B1 |
| Client secret | from B1 |
| Authorization endpoint | `https://sits.edu.et/oauth/authorize` |
| Token endpoint | `https://sits.edu.et/oauth/token` |
| Userinfo endpoint | `https://sits.edu.et/oauth/userinfo` |
| Scopes included in a login request | `openid profile email` |
| This service will be used | Login page and internal services |
| Require email verification | No (SITS already verifies) |

Then *Configure user field mappings* on that service:

| External field name | Moodle user field |
|---|---|
| `email` | email |
| `given_name` | firstname |
| `family_name` | lastname |

Moodle's redirect URI is fixed at `https://learn.sits.edu.et/admin/oauth2callback.php` —
that is what `moodle:oauth-client` registers. If your Moodle reports a different
callback, re-run the command with `--redirect=<the exact URL Moodle shows>`.

### B3. Enable the OAuth 2 authentication plugin

*Site administration → Plugins → Authentication → Manage authentication* → enable
**OAuth 2**. Then in its settings:

- **Prevent account creation when authenticating**: **No** — lets a first-time SITS
  user get a Moodle account automatically.
- **Auto-confirm linked accounts**: **Yes** — otherwise the user gets a confirmation
  email, which is a second hoop and defeats the point.

Existing Moodle accounts link by email on first OAuth login.

### B4. Verify

Sign in at sits.edu.et, then open `https://learn.sits.edu.et` in the same browser and
press **Log in with SITS**. You should land in Moodle without typing anything.

---

## Path A — auth_userkey (the zero-click website link)

### A1. Install the plugin

[Catalyst `auth_userkey`](https://moodle.org/plugins/auth_userkey) — the release from
August 2025 supports Moodle 4.5 and 5.0. Install it into the Moodle **code root**
(the directory holding `config.php`), then run the upgrade:

```bash
cd ~/moodle            # or wherever config.php lives
# unzip the plugin into auth/userkey, then:
ea-php83 admin/cli/upgrade.php --non-interactive
```

### A2. Configure it

*Site administration → Plugins → Authentication → Manage authentication* → enable
**User key authentication**, then open its settings:

| Setting | Value | Why |
|---|---|---|
| User mapping field | **email** | must match `MOODLE_SSO_MAP` on the SITS side |
| IP restriction | **Yes** | |
| Allowed IPs | the SITS web server's IP | see the warning below |
| Key life time | 60 seconds | the key is used within one redirect |

> **This is the security-critical step.** A valid token for
> `auth_userkey_request_login_url` can mint a login link for *any* Moodle account,
> including admins. The IP allow-list and a tightly scoped token are what stop that
> token from being a full site takeover. Do not skip them, and do not put the token
> anywhere but `.env`.

### A3. Create the web service token

1. *Site administration → Server → Web services → Overview* — enable web services and
   the **REST** protocol.
2. *External services* → **Add** a service named `sits_sso_service`, enabled,
   *Authorised users only* ticked.
3. Add these functions to it — and **only** these:
   - `auth_userkey_request_login_url`
   - `core_user_get_users`
   - `core_user_create_users`
   - `core_user_update_users`
4. Create a dedicated Moodle user for the integration (e.g. `sits-sso`), give it a role
   with just the capabilities those functions need, and add it as an *authorised user*
   of the service. Do **not** issue the token against a site admin.
5. *Manage tokens* → create a token for that user on `sits_sso_service`. Set the token's
   **IP restriction** to the SITS server IP here too.

### A4. Wire it into the SITS `.env`

```env
MOODLE_URL=https://learn.sits.edu.et
MOODLE_TOKEN=<the token from A3>
MOODLE_SSO_SERVICE=sits_sso_service
MOODLE_SSO_MAP=email
```

```bash
cd ~/sits
ea-php83 artisan config:cache
```

`MOODLE_SSO_MAP` must equal the *User mapping field* chosen in A2 — `MoodleService`
sends that one field and nothing else, so a mismatch means "user not found".

### A5. Verify

Sign in at sits.edu.et and click *eLearning → Moodle* (`/go/lms`). You should arrive
inside Moodle already authenticated, with no login page at all.

If instead you land on Moodle's login page, the fallback did its job and something in
A is misconfigured. The reason is logged on the SITS side:

```bash
cd ~/sits && tail -n 50 storage/logs/laravel.log | grep -i moodle
```

---

## What each side is trusting

- **Path B** trusts nothing implicitly: Moodle receives a short-lived authorization code
  and exchanges it over TLS using its client secret.
- **Path A** trusts the SITS server to speak for any user. That trust is bounded by the
  web service token, the IP allow-list, and the 60-second key lifetime.
- `/go/lms` is behind the `auth` middleware only — any signed-in SITS user may follow it.
  Moodle still decides what they can *see*: enrolment and roles are enforced there, and
  the `roles` claim in `/oauth/userinfo` is what a Moodle-side role mapping can read.

## Rollback

Both paths fail safe and can be turned off independently without touching code:

- Disable A: remove `MOODLE_TOKEN` from `.env`, `artisan config:cache`. `/go/lms`
  reverts to a plain redirect to the Moodle login page.
- Disable B: disable the OAuth 2 auth plugin in Moodle, or revoke the client with
  `ea-php83 artisan passport:client:delete <id>`.
- Undo the consent skip only: remove `MOODLE_OAUTH_CLIENT_ID`, `artisan config:cache`.
