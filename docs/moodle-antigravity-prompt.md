# Prompt — Install Moodle LMS on the SITS cPanel server + integrate SSO with sits.edu.et

> Paste everything below (from "You are" to the end) into the Antigravity IDE agent that has
> access to the SITS cPanel hosting and the SITS website codebase. Fill in the `<<...>>` blanks
> first. Do the RECON step and report before making any changes.

---

You are a senior DevOps + Laravel/Moodle engineer. Install a **new production Moodle LMS** on our
**cPanel shared hosting** and integrate **single sign-on** with our existing website so one SITS
account logs into both. Work carefully on a live server: **back up before changing anything**, do
read-only recon first, and confirm the plan before installing.

## Environment (facts)
- Main website: **https://sits.edu.et** — a **Laravel 12** app (Blade + Inertia/Vue) on cPanel.
- cPanel account user: `<<CPANEL_USER e.g. sitseduorg>>`; SSH is key-only (password auth disabled).
- There is an **old Moodle at https://lms.sits.edu.et** — **IGNORE IT, do not touch or migrate it.**
- We want the NEW Moodle on its own subdomain, e.g. **https://learn.sits.edu.et** (pick this unless
  taken). MariaDB/MySQL is available via cPanel.
- The website already has these pieces committed (deploy them if not live yet):
  - An **OAuth2/OIDC identity provider** via Laravel Passport with endpoints
    `https://sits.edu.et/oauth/authorize`, `https://sits.edu.et/oauth/token`, and a custom
    **`https://sits.edu.et/oauth/userinfo`** (returns `sub,email,name,given_name,family_name,roles[]`).
  - A nav **"eLearning" dropdown** → *SITS LMS* (external lms.sits.edu.et) and *Moodle* (`/go/lms`).
  - `/go/lms` (route `lms.redirect`) redirects the logged-in user to `MOODLE_URL` (set this to the
    new Moodle once live).

## Objective
1. Stand up the newest Moodle the server's PHP supports, on `learn.sits.edu.et`, with its own DB.
2. Configure Moodle to log users in **via the SITS website** (OAuth2), matching by email.
3. Point the website's "Moodle" nav item at the new site and verify one-login works end-to-end.

---

### STEP 0 — Recon (READ-ONLY; report results, then wait for go-ahead)
- cPanel/PHP: available PHP versions (MultiPHP Manager) and the default for the account.
- Whether **Softaculous** (Software → Softaculous Apps Installer) is available (it has 1-click Moodle).
- MySQL: version; confirm you can create a new DB + user.
- SSH/Terminal availability; `php -v`; presence of `git`, `unzip`, `wget/curl`.
- Disk quota / free space (Moodle code ~1GB + moodledata growth).
- Confirm `learn.sits.edu.et` (or chosen subdomain) is free and DNS can point to this server.

### STEP 1 — Pick the Moodle version by PHP (hard requirement)
| Server PHP | Newest compatible Moodle |
|---|---|
| 8.3+ | **Moodle 5.2** (git branch `MOODLE_502_STABLE`) |
| 8.2  | **Moodle 5.1** (`MOODLE_501_STABLE`) |
| 8.1  | **Moodle 4.5 LTS** (`MOODLE_405_STABLE`) |
Prefer 8.3 + Moodle 5.2 if you can set the subdomain's PHP to 8.3 in MultiPHP Manager. Otherwise use
the row matching the PHP you can enable. **Moodle 5.x uses a `public/` web root** (docroot must point
to `.../moodle/public`, and `version.php`/`config.php` live under `public/`).

### STEP 2 — Provision (subdomain, DB, PHP)
1. **Subdomain**: create `learn.sits.edu.et`. Set its **document root to `.../moodle/public`** (for
   Moodle 5.x). Enable HTTPS (AutoSSL/Let's Encrypt).
2. **Database**: cPanel → MySQL® Databases → create DB `<<user>>_moodle` + a user with a strong
   password, **grant ALL privileges**. Charset **utf8mb4**.
3. **PHP for the subdomain**: MultiPHP Manager → set to the version chosen in Step 1. Then
   **enable extensions**: `intl, soap, sodium, curl, gd, mbstring, zip, xml, intl, exif, fileinfo,
   openssl, ctype, iconv, simplexml, tokenizer`. MultiPHP INI Editor → set
   `max_execution_time=300`, `memory_limit=256M`, `post_max_size=100M`, `upload_max_filesize=100M`,
   `max_input_vars=5000`.

### STEP 3 — Install Moodle (choose A or B)
**A. Softaculous (easiest, if available):** Softaculous → Moodle → Install → choose the
`learn.sits.edu.et` domain, the DB from Step 2, admin user/email/password. Verify it installs the
version from Step 1 (if Softaculous only offers an older release, prefer manual B for the newest).

**B. Manual (git):** From SSH/Terminal in the account home:
```bash
# clone the branch chosen in Step 1 (example shows 5.1)
git -c core.longpaths=true clone --depth 1 --branch MOODLE_501_STABLE \
  https://github.com/moodle/moodle.git ~/moodle
# moodledata OUTSIDE the web root:
mkdir -p ~/moodledata && chmod 750 ~/moodledata
# point the learn.sits.edu.et docroot at ~/moodle/public (Step 2), then run the CLI installer:
php ~/moodle/admin/cli/install.php \
  --wwwroot="https://learn.sits.edu.et" \
  --dataroot="$HOME/moodledata" \
  --dbtype=mariadb --dbhost=localhost --dbname="<<user>>_moodle" \
  --dbuser="<<user>>_moodle" --dbpass="<<DB_PASSWORD>>" --prefix=mdl_ \
  --fullname="SITS eLearning" --shortname="SITS LMS" \
  --adminuser=admin --adminpass="<<STRONG_ADMIN_PASS>>" \
  --adminemail="sitsethiopia@gmail.com" --lang=en --non-interactive --agree-license
```
If PHP CLI ≠ the web PHP, use the account's versioned CLI binary (e.g. `ea-php83`). Ensure
`config.php` ends up at `~/moodle/public/config.php` and `$CFG->wwwroot='https://learn.sits.edu.et'`.

### STEP 4 — Cron (required)
cPanel → Cron Jobs → every minute:
```
* * * * * /usr/local/bin/php <<PHP_CLI>> ~/moodle/admin/cli/cron.php >/dev/null 2>&1
```

### STEP 5 — Single Sign-On (Moodle ⇄ SITS website)
**On the SITS website (Laravel), make sure the IdP is live in production:**
```bash
composer require laravel/passport            # if not already installed
php artisan migrate --force                  # creates oauth_* tables
php artisan passport:keys --force            # generate encryption keys (keep out of git)
# create a CONFIDENTIAL authorization-code client for Moodle (Moodle needs a client SECRET):
php artisan passport:client                  # name: "Moodle LMS";
                                             # redirect: https://learn.sits.edu.et/admin/oauth2callback.php
# ⚠ Do NOT use `--public` / PKCE — Moodle's OAuth 2 service form requires a client_secret, which a
#   public client does not have. Use the plain confidential client above.
```
Confirm `config/auth.php` has an `api` guard `driver=passport`, the `User` model uses
`Laravel\Passport\HasApiTokens`, and `GET /oauth/userinfo` (middleware `auth:api`) returns
`email,name,given_name,family_name,roles`. Set in the website `.env`:
`MOODLE_URL=https://learn.sits.edu.et`. **Record the client_id + client_secret** from `passport:client`.

**In Moodle (Site administration):**
1. Server → **OAuth 2 services** → *Create new custom service*:
   - Name: `SITS SSO`
   - Client ID / secret: the values from `passport:client` above.
   - **Authorization endpoint**: `https://sits.edu.et/oauth/authorize`
   - **Token endpoint**: `https://sits.edu.et/oauth/token`
   - **Resource owner / userinfo endpoint**: `https://sits.edu.et/oauth/userinfo`
   - Scopes: `openid profile email`
   - "This service will be used for internal identity issuer/login": **yes**.
   - Field mappings: `email→email`, `given_name→firstname`, `family_name→lastname`.
2. Plugins → Authentication → **Manage authentication** → enable **OAuth 2**. In its settings, allow
   the `SITS SSO` service for login; set "Prevent account creation when authenticating" = **No**
   (auto-provision) but require verified email match.
3. (Optional) Map SITS `roles[]` → Moodle roles (e.g. STUDENT→student, TRAINER→editingteacher,
   STAFF→manager) via a small local plugin or an admin-side sync; students/staff otherwise land as
   authenticated users.

### STEP 6 — Wire the website nav
The "Moodle" item (and `/go/lms`) already resolve to `MOODLE_URL`; with Step 5 set, clicking
**eLearning → Moodle** takes a logged-in SITS user straight into `learn.sits.edu.et` via SSO.
Leave **eLearning → SITS LMS** pointing at the old `lms.sits.edu.et`.

### STEP 7 — Verify (report evidence)
- `https://learn.sits.edu.et` loads the Moodle login with a **"Log in via SITS SSO"** button.
- Logging in as an existing SITS user creates/matches the Moodle account by email and lands in Moodle.
- Site admin → Notifications shows no critical env failures; cron runs.
- From the SITS site, **eLearning → Moodle** performs the SSO round-trip with no second login.

## Guardrails
- Do **not** modify or migrate the old `lms.sits.edu.et`.
- Back up DB + files before any destructive step; work on the new subdomain only.
- Keep `passport:keys`, DB passwords, and client secrets out of version control.
- If the newest Moodle needs a PHP the server can't provide, install the next compatible version
  (Step 1 table) rather than forcing an unsupported combo.
