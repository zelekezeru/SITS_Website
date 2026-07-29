# SITS_Website

Three applications in one Laravel repo for sits.edu.et: **public website**, **ERP** (HR, payroll, attendance, evaluation), and **Library ILS**. Laravel 12 + Inertia 2 + Vue 3 (Tailwind 4, Vite). **PHP 8.2** — no 8.3-only syntax.

## ⚠️ Local environment is broken — read first

`vendor/` is incomplete: only `breeze, framework, pint, prompts, sail, serializable-closure, tinker` are installed. Passport, Scout, Inertia and Spatie are in `composer.lock` but **not on disk**, so `php artisan` fails at boot with `Class "Laravel\Passport\Passport" not found` (`AppServiceProvider:43`) and **the test suite cannot run at all**.

Likely cause: local PHP 8.2 has **no ext-sodium** (confirmed), which Passport needs — `docs/deploy-to-cpanel.md` references a local `--ignore-platform-req` workaround. Fix the install before trusting any local run; don't interpret the boot error as something you broke.

## Layout

Standard Laravel, no domain modules. Routes are split and chained from `web.php`: `routes/web.php` (public site) `require`s `erp.php` (L119) and `library.php` (L123). Controllers subfoldered `Admin/`, `Finance/`, `Library/`, `Portal/`.

Heavily enum-driven — `app/Enums` has ~40 (`Role`, `Permission`, `PayrollStatus`, `AttendanceStatus`, `BookStatus`, `Cadence`, …). Prefer an existing enum over a string literal; check there first.

## Key patterns

**AI analysis layer.** `Services\Ai` uses a driver pattern: `AiServiceManager` resolves an `AiAnalysisContract` implementation — `ClaudeProAnalyzer`, `GoogleGeminiProAnalyzer`, or `MockAnalyzer` — selected by the `AiProvider` enum. Models are config-driven in `config/ai.php` (`CLAUDE_PRO_MODEL`, default `claude-opus-4-8`; `GEMINI_PRO_MODEL`). Use `MockAnalyzer` in tests; never hardcode a model id at a call site.

**Moodle SSO.** Laravel Passport is the OAuth2 *server*; Moodle is the consumer. Consent view is a local Blade (`oauth.authorize`), registered in `AppServiceProvider` — no `vendor:publish`. Client secrets are stored plain text deliberately, so Moodle can authenticate. See `docs/moodle-integration.md`.

**Attendance ingest.** Hikvision devices post to a webhook (`HikvisionWebhookTest` covers it); imports land as `AttendanceImport` → `AttendanceImportRow` with match status/method enums before becoming `AttendanceRecord`.

**Search.** Scout + Meilisearch. **Roles.** Spatie permission, but note *two* role middlewares exist — `EnsureRole` and `EnsureUserHasRole`. Check which a route group uses before adding a third.

## Working a task

1. **Restate the goal** in one line: outcome, not steps. If the request reads two ways, ask before building the wrong thing.
2. **Decompose** into small, independently verifiable steps. Show the plan before a multi-file change.
3. **Gather context narrowly.** Grep/Glob to locate, then read only the ranges that matter.
4. **Hold scope.** Three apps share this repo — a change in ERP can surface on the public site. Flag adjacent problems; don't fix them uninvited.

Economy, never at the cost of correctness: read ranges not whole files; don't re-read after an edit; batch independent tool calls. When brevity and quality conflict, quality wins and you say why.

## Testing

Pest (`tests/Pest.php`), `RefreshDatabase` on all Feature tests. Currently unrunnable locally — see the environment note above. Once fixed, run `php artisan test` and `npm run build` before committing.

## Critical: assets are committed

Production is cPanel with **no Node**. `public/build` is tracked in git (incl. `manifest.json`, pdf.js worker, qr-reader), built locally and pulled on the server. Any change under `resources/` needs `npm run build` plus the regenerated `public/build` in the same commit. Deploy runbook: `docs/deploy-to-cpanel.md` — it replaces the live site, so staging + backups first.

`ViteManifestNotFound` in production = stale or missing committed `public/build`.

## Gotcha

cPanel paths can carry stray newline characters that break namespace/class detection. Trim path strings before using them in autoload or path config.
