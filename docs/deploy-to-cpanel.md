# Deploy the merged SITS system to cPanel (sits.edu.et)

Deploys this repo (Laravel 12 + Inertia/Vue — Website + ERP + Library ILS + Moodle SSO)
to the production cPanel host. **This replaces the current live site.** Do it in a
maintenance window, on a **staging copy first**, with full backups.

> Prereqs: cPanel access; **PHP 8.2+ (ideally 8.3)** set for the domain in MultiPHP
> Manager; a MySQL DB; Composer available (SSH or cPanel "Terminal"); the code pushed
> to `github.com/zelekezeru/SITS_Website` (`main`). Prebuilt assets are committed in
> `public/build`, so **Node is NOT required on the server**.

## STEP 0 — Backups (do first, no exceptions)
- Full **mysqldump** of the current production DB.
- Tarball of the current `public_html` (or the app dir) + `storage`.
- Note the current PHP version and `.env`.

## STEP 1 — Get the code onto the server
Option A (cPanel Git™ Version Control): create/refresh a clone of
`https://github.com/zelekezeru/SITS_Website.git` (branch `main`) and **Pull or Deploy HEAD**.
Option B (SSH): `git clone` (first time) or `cd repo && git fetch && git reset --hard origin/main`.
The app must live **outside** the web root, with the domain's document root pointed at the
repo's **`public/`** directory.

## STEP 2 — PHP deps
```bash
composer install --no-dev --optimize-autoloader
```
(Production PHP has ext-sodium, so the local `--ignore-platform-req` hack is NOT needed.)

## STEP 3 — Assets
Already built and committed under `public/build` (incl. the library pages, pdf.js worker,
qr-reader). Just confirm `public/build/manifest.json` exists. No `npm` needed.

## STEP 4 — .env (production)
Copy the server's existing `.env` (keep APP_KEY!) and set:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sits.edu.et
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<<prod_db>>
DB_USERNAME=<<prod_user>>
DB_PASSWORD=<<prod_pass>>
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
SCOUT_DRIVER=database          # or meilisearch if you run the service
# Integrations (already wired in code):
GEMINI_PRO_API_KEY=<<your Gemini key>>
GEMINI_MODEL=gemini-2.5-flash
LIBRARY_AI_PROVIDER=gemini
MOODLE_URL=https://learn.sits.edu.et
CHAPA_SECRET_KEY=            # optional; empty => manual fine gateway
CHAPA_PUBLIC_KEY=
```
If it's a brand-new app dir with no APP_KEY: `php artisan key:generate`.

## STEP 5 — Database (DECIDED: prod already has the ERP merge → additive Library-only deploy)
The current prod DB already carries the ERP-merged schema, so this deploy only **adds**
the Library tables and grants the Library permissions to the roles that already exist.
Nothing ERP/website is dropped or reseeded. **Back up first (STEP 0), then:**
```bash
php artisan migrate --force        # adds the 27 Library tables; ALTERs the shared `campuses`
                                   # table (adds name/code/address/soft-deletes). Additive only.

# Seed ONLY the Library permissions — idempotent (firstOrCreate + givePermissionTo),
# attaches to the existing SITS roles (SUPERADMIN/LIBRARIAN/TRAINER/…).
php artisan db:seed --class=LibraryPermissionsSeeder --force
```
> ⚠️ Do **NOT** run a blanket `php artisan db:seed --force` on this live DB. The default
> `DatabaseSeeder` re-runs the ERP/website seeders (duplicating reference data) and creates
> an `admin@sits.edu.et` / `password` account. Only the class-scoped seed above is safe here.

Never run `migrate:fresh` on a DB you want to keep — it drops everything.

## STEP 6 — Wire-up & caches
```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```
Ensure `storage/` and `bootstrap/cache/` are writable. Add a cron entry:
`* * * * * php /path/to/app/artisan schedule:run >/dev/null 2>&1`

## STEP 7 — Verify
- `https://sits.edu.et` loads (public site), login works.
- `/portal` shows the ERP/LMS/Library cards; `/library/dashboard` opens the ILS.
- ERP admin loads; Moodle SSO round-trips (once the Moodle side is configured).
- Check `storage/logs/laravel.log` for errors; `php artisan about` shows env=production, debug=off.

## STEP 8 — Rollback
Restore the DB dump + the app/`storage` tarball, point the docroot back, clear caches.

---

## Copy-paste deploy sequence (SSH / cPanel Terminal) — additive Library deploy
Run from the app root on the server (the dir whose `public/` is the docroot):
```bash
# 0. BACK UP FIRST (see STEP 0) — mysqldump + tarball of app/storage.

# 1. Pull the code (origin/main is current; all commits are pushed)
git fetch origin && git reset --hard origin/main

# 2. PHP deps (prod has ext-sodium; no --ignore-platform-req needed)
composer install --no-dev --optimize-autoloader

# 3. .env — confirm APP_ENV=production, APP_DEBUG=false, DB_* correct, keep APP_KEY (STEP 4)

# 4. DB — additive migrate + Library permissions only (STEP 5)
php artisan migrate --force
php artisan db:seed --class=LibraryPermissionsSeeder --force

# 5. Wire-up & caches (STEP 6)
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize

# 6. Verify (STEP 7): / , /portal , /library/dashboard ; php artisan about (env=production, debug=off)
```
Assets are prebuilt in `public/build` (166 files, manifest present) — **no `npm`/Node on the
server.** If Meilisearch isn't running, keep `SCOUT_DRIVER=database` in `.env`; switch to
`meilisearch` and run `php artisan scout:import "App\Models\Book"` later.

**Status:** DB strategy = option 1 (prod already ERP-merged) ✅ · `origin/main` up to date ✅
