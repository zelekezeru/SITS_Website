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

## STEP 5 — Database (THE key decision — see below)
After choosing the DB strategy and backing up:
```bash
php artisan migrate --force            # additive: brings ERP + Library tables
# first-time only (fresh DB): also seed roles/permissions/reference data
php artisan db:seed --force
```
`migrate` reconciles `campuses` automatically (the merge's ALTER migration). Never run
`migrate:fresh` on a DB you want to keep — it drops everything.

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

## The one decision I need: the production database
The merged app needs the full 117-table schema. What is the **current** production DB?
1. **It already has the ERP-merged schema** (you've been deploying the merge) → just
   `php artisan migrate --force` to add the Library tables. Lowest risk. **(likely)**
2. **It's still the old website-only DB** → migrate will add ERP + Library tables on top;
   test on a staging copy first (foreign-key/order edge cases), then run on prod.
3. **Start clean** → new empty DB + `migrate --force && db:seed --force`, then import real
   users/content. Cleanest, but you re-enter/import production data.

Tell me which, and whether I should **push the 6 local commits to `origin/main`** so the
server can pull them.
