# Moodle migration & two-site split (old.sits.edu.et + learn.sits.edu.et)

Goal:
1. **old.sits.edu.et** serves the **old Moodle** from `~/moodle-old`, with its old database and all
   existing users still able to log in — kept as a read-only-ish **archive**.
2. **sits.edu.et** (the Laravel site) links to the **new Moodle** in `~/moodle`, served at
   **learn.sits.edu.et** (already wired: `.env` `MOODLE_URL=LMS_URL=https://learn.sits.edu.et`,
   nav *eLearning → Moodle* → `/go/lms`, OIDC IdP built on the Laravel side).
3. **Complete migration** of *all* old data into the new Moodle so nothing is lost.

> Everything here runs **on the cPanel server** (SSH / cPanel Terminal). Run the tool with the
> account's real PHP, e.g. `ea-php83`. The tool `scripts/moodle/moodle-migrate.php` reads all
> DB creds/paths/URLs out of each Moodle's `config.php` — you don't type credentials.

## Why "complete" = clone-and-upgrade (not backup/restore)
Moodle course **backup/restore** (`.mbz`) moves *course content* but **not** global users, site
settings, cohorts, roles, or cross-course data. The only way to migrate **everything** is to copy
the **old database + old `moodledata`** into the new site and run **Moodle's upgrade**, which
migrates the schema forward version-by-version. So the new Moodle's DB is *replaced* by the
upgraded old DB — any content in the fresh new install is discarded (that's inherent to a full
migration).

---

## Target layout on the server
| Subdomain | Document root (dir that contains `config.php`) | Code | Database | moodledata |
|---|---|---|---|---|
| **old.sits.edu.et** | `~/moodle-old` *(or `~/moodle-old/public` if it's Moodle 5.x)* | old | old DB (unchanged) | old `moodledata` |
| **learn.sits.edu.et** | `~/moodle/public` *(or `~/moodle` if the new build is pre-5.x)* | new | new DB → *becomes clone of old, upgraded* | new `moodledata` → copy of old |

**Rule of thumb for docroot:** point it at the directory that physically contains `config.php`.
Moodle 5.x uses a `public/` webroot; 4.x and earlier use the code root. The recon output prints the
exact `code root  → docroot MUST point here` for each site.

---

## Phase A — Recon (read-only; do this first)
```bash
cd ~
ea-php83 /path/to/repo/scripts/moodle/moodle-migrate.php recon
# (or copy the script to ~ and run: ea-php83 ~/moodle-migrate.php recon)
```
It prints, for BOTH installs: config path, **detected version** (`$release` + DB `version`), db
name/host/user/prefix, dataroot, wwwroot, and a live DB probe (users / courses / tables). It also
prints the **upgrade jump** (old → new) and flags whether it's a single hop or a major jump, plus
the docroots to set. **Read this before doing anything else** — it's how we confirm the old version
on the server.

---

## Phase B — Wire the subdomains (cPanel UI, one-time)
cPanel → **Domains** (or *Subdomains*) → for each subdomain set the **Document Root**:
- `old.sits.edu.et`  → the OLD code root from recon (e.g. `~/moodle-old`)
- `learn.sits.edu.et` → the NEW code root from recon (e.g. `~/moodle/public`)

Then MultiPHP Manager → set each subdomain's PHP to a version the respective Moodle supports
(new Moodle 5.x needs **PHP 8.3**; the old one needs whatever it originally ran on). Enable
AutoSSL for both. Confirm extensions on the NEW site's PHP: `intl, soap, sodium, curl, gd,
mbstring, zip, xml, exif, fileinfo, openssl, ctype, iconv, simplexml, tokenizer`.

---

## Phase B2 — Preflight (do BEFORE Phase C): third-party plugins + character set
Two things the clone-and-upgrade can silently mangle if you skip them. Both are quick to check.

### B2.1 — Match the OLD site's third-party plugins into the NEW code tree
**Why:** the cloned OLD database records every plugin the OLD site had installed. If the NEW code
tree doesn't physically contain those plugins, `admin/cli/upgrade.php` reports them as *missing* and
their tables/data are orphaned (Moodle offers to uninstall them → data loss). Core plugins are fine;
this is only about **contributed / third-party** ones.

1. **List the OLD site's additional plugins.** Canonical: OLD Moodle → *Site administration → Plugins →
   Plugins overview* → tick **"Show additional plugins only"**. CLI alternative (Moodle 3.9+):
   ```bash
   ea-phpXX ~/moodle-old/admin/cli/uninstall_plugins.php --show-all   # non-core rows = your contrib plugins
   ```
2. **Copy each one into the NEW tree at the same relative path** (`mod/…`, `blocks/…`, `theme/…`,
   `local/…`, `auth/…`, `enrol/…`, `filter/…`, `question/type/…`, `report/…`). Use a release of the
   plugin that supports the **NEW** Moodle version — an old copy that predates the target version will
   itself fail the upgrade. Get compatible builds from the plugin's page on moodle.org/plugins.
3. **If a plugin has no build compatible with the new version,** decide deliberately to drop it and let
   Moodle uninstall it during the upgrade (its data goes) — don't leave stale incompatible code in the
   tree. Do this **before** running Phase C (or, for a hop upgrade, before the *final* hop's upgrade).

### B2.2 — Confirm the database character set (utf8mb4)
**Why:** this runbook and the tool assume **utf8mb4** end to end. An older Moodle on `utf8` (utf8mb3)
or `latin1` can fail the upgrade or corrupt multibyte text (Amharic, emoji, smart quotes) when loaded
under a utf8mb4 connection.

1. **Read-only check on the OLD DB** (changes nothing):
   ```bash
   ea-phpXX ~/moodle-old/admin/cli/mysql_collation.php --list    # all utf8mb4_* ? → nothing to do
   ```
   Moodle sites created in the last several years are already utf8mb4 → this is usually a no-op.
2. **If OLD is not utf8mb4,** convert the *NEW* DB right after the clone and before the upgrade, so the
   OLD archive stays untouched. Split Phase C into clone → convert → finish:
   ```bash
   ea-phpXX ~/moodle-migrate.php run --confirm --skip-upgrade                    # clone db+files only
   ea-phpXX ~/moodle/admin/cli/mysql_collation.php --collation=utf8mb4_unicode_ci  # convert in place
   ea-phpXX ~/moodle-migrate.php run --confirm --skip-db --skip-files            # upgrade + URL rewrite + purge
   ```
   (`--skip-upgrade` clones and matches the prefix but stops before the upgrade; the final call does
   only the upgrade, URL rewrite, and cache purge — no re-clone.)

---

## Phase C — Complete migration (OLD → NEW)
> **Run Phase B2 first.** If third-party plugins aren't in the NEW tree, the upgrade orphans their
> data; if the OLD DB isn't utf8mb4, the upgrade can choke or mojibake content.

Backs up the new DB, clones the old DB + moodledata into the new site, upgrades, rewrites URLs,
purges caches. **Destructive to the NEW database** (backed up first, under `~/moodle-migrate-backups/`).

```bash
# dry run first (prints exactly what it will do, changes nothing)
ea-php83 ~/moodle-migrate.php run
# then execute
ea-php83 ~/moodle-migrate.php run --confirm
```
What it does, in order: backup NEW db → dump OLD db → empty NEW db → load OLD data → match table
prefix → copy `moodledata` (rsync, minus regenerable caches) → **`admin/cli/upgrade.php`** →
rewrite old wwwroot → `learn.sits.edu.et` in content (`admin/tool/replace`) → purge caches.

### If the upgrade step is rejected (major version jump)
Moodle refuses to upgrade across too many releases at once. The clone still succeeded; do a **hop
upgrade** using the NEW code dir as a scratch area, then finish without re-cloning.

**Upgrade hop table** (each hop needs its own PHP; do them in order until you reach the new version):

| From (old release) | Hop through (each: check out branch → run `admin/cli/upgrade.php`) |
|---|---|
| 4.1–4.4 | → **5.x** directly (usually one step) |
| 3.11 | → 4.1 LTS → 4.5 LTS → 5.x |
| 3.5–3.10 | → 3.11 → 4.1 LTS → 4.5 LTS → 5.x |
| 2.7–3.4 | → 3.5 → 3.11 → 4.1 → 4.5 → 5.x (long; consider fresh + course restore instead) |

Per hop, in the NEW code dir (back up `config.php` first — keep the same DB/dataroot/prefix):
```bash
cd ~/moodle                                   # the NEW code dir (git checkout of Moodle)
cp public/config.php ~/config.php.keep 2>/dev/null || cp config.php ~/config.php.keep
git fetch --depth 1 origin MOODLE_401_STABLE  # example hop: 4.1 LTS
git checkout MOODLE_401_STABLE
cp ~/config.php.keep public/config.php 2>/dev/null || cp ~/config.php.keep config.php
ea-php81 admin/cli/upgrade.php --non-interactive   # use the PHP that branch requires
# repeat for the next branch (MOODLE_405_STABLE, then MOODLE_50x_STABLE …)
```
When you reach the target version, finish the migration bookkeeping (URL rewrite + caches) without
re-cloning:
```bash
ea-php83 ~/moodle-migrate.php run --confirm --skip-db --skip-files
```
> Moodle PHP requirements per line: 5.x → PHP 8.3 · 5.1 → 8.2 · 4.5/4.1 LTS → 8.1 · 3.11 → 7.4 ·
> 3.5 → 7.1. Set the matching PHP in MultiPHP (or use the versioned CLI `ea-phpXX`) for each hop.

---

## Phase D — Archive the OLD site at old.sits.edu.et
Re-point the old install's URL so its links resolve under the archive domain:
```bash
ea-php83 ~/moodle-migrate.php fix-old            # dry run
ea-php83 ~/moodle-migrate.php fix-old --confirm  # sets old wwwroot → old.sits.edu.et,
                                                 # rewrites old content URLs, purges old caches
```
The old DB and users are untouched, so existing accounts keep working. (Optional: Site admin →
put it in read-only via *maintenance* or disable self-enrolment if it's purely an archive.)

---

## Phase E — SSO: new Moodle logs in via sits.edu.et
The Laravel IdP (Passport) is already built. Create a **confidential** OAuth client for Moodle and
wire it in Moodle's *OAuth 2 services* (full detail in [moodle-antigravity-prompt.md](moodle-antigravity-prompt.md), STEP 5):
```bash
# on the SITS Laravel app
php artisan passport:client   # name "Moodle LMS";
                              # redirect https://learn.sits.edu.et/admin/oauth2callback.php  (NOT --public)
```
Moodle → Site admin → Server → **OAuth 2 services** → custom service:
- Auth endpoint `https://sits.edu.et/oauth/authorize`, Token `https://sits.edu.et/oauth/token`,
  Userinfo `https://sits.edu.et/oauth/userinfo`, scopes `openid profile email`,
  field maps `email→email, given_name→firstname, family_name→lastname`.
- Plugins → Authentication → enable **OAuth 2**; allow the service; match by verified email.

Because migrated users keep their old email addresses, SSO matches them to their existing
(migrated) Moodle accounts — no duplicate accounts.

---

## Phase F — Verify & rollback
**Verify (new site):** `https://learn.sits.edu.et` loads; log in as an existing (migrated) user;
open a course, check grades, an uploaded file, and a forum post; Site admin → Notifications shows no
critical failures; `admin/cli/cron.php` runs (add the per-minute cron). From sits.edu.et,
**eLearning → Moodle** does the SSO round-trip.
**Verify (archive):** `https://old.sits.edu.et` loads and old users can still log in.

**Rollback (new site):** restore the pre-migration NEW db and re-point its config — the tool saved
`~/moodle-migrate-backups/newdb-BEFORE-<timestamp>.sql`:
```bash
mysql -u <newuser> -p <newdb> < ~/moodle-migrate-backups/newdb-BEFORE-<timestamp>.sql
```
The OLD site is never modified by `run` (only by `fix-old`, which backs up its `config.php`).

---

## Cron (both Moodle sites need it)
cPanel → Cron Jobs → every minute, per site:
```
* * * * * /opt/cpanel/ea-php83/root/usr/bin/php ~/moodle/admin/cli/cron.php >/dev/null 2>&1
```
(Adjust the PHP path/binary and the code dir per site.)
