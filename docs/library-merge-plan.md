# SITS Library (ILS) → SITS_Website merge plan

Merge the standalone **`E:\WebDev\sits-library`** into this repo (`SITS_Website`) as one system on a
single database, keeping everything, with i18n, light/dark theme, unified RBAC, SSO, and Claude+Gemini AI.

## What sits-library is
- **Stack: Laravel 12 + Inertia + Vue 3 + Tailwind + spatie/permission + Ziggy** — *identical* to the
  merged SITS_Website stack, so the merge is clean (like the earlier ERP merge).
- A full **Integrated Library System (ILS)**, not the lightweight "Library" (resources+subscriptions)
  the SITS site currently has. Features:
  - **Catalog**: Book, BookCopy, Author, Category; ISBN lookup; AI cataloging hooks; QR/barcode labels.
  - **Circulation**: Loan, Hold, Fine, Payment; checkout/return desk; patron self-service (my loans/holds/fines).
  - **Spatial shelving**: Campus → Floor → Row → ShelfBox; QR **Scan/Place**; PlacementLog; Transfer between campuses.
  - **Stocktake** (inventory scans), **BookReview**, **SecureDocument** (access/view-tracked PDFs) + **Archive Reader** (pdf.js).
  - **ExternalResource** (JSTOR/IEEE/DOAJ… with click tracking), **Reports** (DomPDF/Excel), **Audit** (activity log).
  - **Legacy import** (staging tables + LegacyImportController) — migrating an old library's data.
- **Auth**: Breeze + **2FA + passkeys + Sanctum + custom HMAC SSO** (SsoRedirect/SsoCallback + ValidateSsoToken).
- **RBAC**: `App\Enums\Role` = super_admin, campus_admin, librarian, instructor, staff_user, student;
  `App\Enums\Permission` (view_books, checkout_book, waive_fine, manage_shelf_box, upload_secure_pdf, …) via spatie; Gate::before for super_admin.
- **Search**: Laravel Scout + **Meilisearch** (:7700). **Payments**: **Chapa** (Telebirr/CBE) for fines.
- **i18n**: JSON lang (`lang/am.json`, `lang/en.json`) — `__('Full English string')` style.
- **Theme**: **light/dark already implemented** (`dark:` in 55 files).
- **AI**: OpenAI-compatible config for "AI cataloging & semantic search" (to be extended to Claude+Gemini).
- DB: `sits-library` on **:3306** (root). SITS_Website: `sits_unified` on **:3308**.

## Conflicts & reconciliation (single DB = `sits_unified`)
| Area | SITS_Website | sits-library | Resolution |
|---|---|---|---|
| `User` model | ERP+website fields, `Laravel\Passport\HasApiTokens` | +`current_campus_id`, loans/holds/fines rels, `Laravel\Sanctum\HasApiTokens`, LogsActivity | Merge fillable+relations; keep **one** ApiTokens trait (Passport, already used for Moodle OIDC); add campus + library relations. |
| Foundational tables | users/cache/jobs/activity_log/notifications/permission/personal_access_tokens exist | same + passkeys/two_factor | Drop library's dup foundational migrations; bring only library-specific ones (like the ERP merge did). |
| Roles | President/Super Admin, ERP roles, SUPERADMIN/ADMIN/EDITOR/LIBRARIAN/USER/STUDENT/TRAINER/STAFF | super_admin/campus_admin/librarian/instructor/staff_user/student | **Unify**: map library→SITS (librarian→LIBRARIAN, student→STUDENT, instructor→TRAINER, staff_user→STAFF, campus_admin→ADMIN, super_admin→SUPERADMIN) OR keep both coexisting + merge the *permission* set. Keep library's granular `App\Enums\Permission`. |
| "Library" concept | `Library` model = resources + `library_subscriptions` + portal/plans | full ILS (books/loans/…) + ExternalResource + SecureDocument | ILS **supersedes** the lightweight one: map SITS `Library` records → `ExternalResource`/`SecureDocument`; retire subscriptions in favor of ILS patron access (or keep subscriptions as an access tier — **decision needed**). |
| SSO | Passport OIDC IdP (built, for Moodle) | custom HMAC token SSO (for separate-subdomain app) | Post-merge the library is **internal** (shared auth/session/DB) → drop its cross-app SSO; keep OIDC for Moodle only. Library nav → internal `/library/*` routes. |
| Search | none | Scout+Meilisearch | Add scout+meilisearch to SITS; run Meilisearch; index Book. (Fallback: Scout `database` driver if no Meilisearch infra — **decision needed**.) |
| i18n | PHP array `lang/en/app.php` (`__('app.key')`) + JS translations | JSON `lang/en.json` (`__('string')`) | Both coexist (Laravel supports JSON + PHP-array simultaneously). Extend both to all target locales. |
| Theme | public site partial dark support | full light/dark | Adopt library's dark-mode system app-wide (toggle + `dark:` variants) for consistency. |
| AI | ERP: claude_pro + gemini config (`AI_*`) | OpenAI-compatible catalog/search | Add **Claude + Gemini** providers; wire AI: ISBN/catalog enrichment, semantic search, recommendations, secure-doc summaries. |
| Payments | manual subscription flow | Chapa (Telebirr/CBE) | Bring Chapa for fines/payments. |
| Frontend | Inertia Pages (ERP + Website) | Inertia Pages (~50) | Namespace library pages under `resources/js/Pages/Library/`; reconcile Tailwind config (SITS uses Tailwind v4 `@theme`). |

## Phased execution (each phase verifiable; safe to stop between)
0. **Prereq**: fix the broken local env (php.ini: re-enable pdo_mysql/intl/soap/sodium) so composer/artisan/build run.
1. **Deps**: add scout, meilisearch-php, maatwebsite/excel, league/csv, simple-qrcode, laravel-backup, pdfjs/vue-qrcode-reader to SITS composer/npm (most already present). `config/services.php` + `config/scout.php` merge; add Claude+Gemini AI config.
2. **DB**: copy library-specific migrations (drop foundational dups) into SITS; add `current_campus_id` + library columns to users; `migrate` into `sits_unified`.
3. **PHP**: copy `app/{Models,Enums,Http,Services,Policies,helpers.php}` (namespaced/deduped); merge `User`, `AppServiceProvider`, `bootstrap/app.php` (middleware, Gate::before).
4. **Routes + RBAC**: library routes → `routes/library.php` (required from web.php), gated by unified roles/permissions; merge RolesAndPermissionsSeeder into the SITS seeder.
5. **Frontend**: library Vue pages → `Pages/Library/*`, components/layouts merged; wire the site nav's Library entry to the internal ILS; apply the light/dark theme site-wide.
6. **i18n**: merge JSON + PHP-array translations; ensure all UI strings localized (am/en + the site's other locales).
7. **AI**: implement Claude+Gemini for cataloging enrichment, semantic search, recommendations, summaries (reuse the ERP AI service pattern; provider-switchable, disabled-by-default).
8. **Search**: stand up Meilisearch (or Scout DB fallback); index Book; wire Catalog search.
9. **Payments**: Chapa for fines (config + webhook).
10. **Verify**: migrate+seed on `sits_unified`, `npm run build`, route:list, smoke-test catalog/circulation/patron/reports + ERP + website + Moodle SSO — nothing regressed.

## Open decisions for the user
1. **Roles**: fully unify library roles into the SITS scheme (recommended), or keep both coexisting?
2. **Existing SITS "Library"** (resources + subscriptions): fold into the ILS (ExternalResource/SecureDocument) and drop subscriptions, or keep subscriptions as an access tier?
3. **Search infra**: run Meilisearch (best search) or use Scout's `database` driver (no extra service)?
4. **Payments**: keep Chapa for fines, or defer payments?
5. **AI scope/providers**: Claude + Gemini for which features first (cataloging, semantic search, recommendations, summaries)?

## Blockers / notes
- **Local env is broken** (php.ini reset lost pdo_mysql/intl/soap/sodium) → nothing can be migrated/built until fixed (Phase 0).
- This is a **large multi-phase merge** (bigger than the ERP merge: 29 models, ~50 Vue pages, 116 routes) — done iteratively, verified per phase.
- The library and SITS both use MariaDB but different servers/ports (3306 vs 3308) — the merge targets `sits_unified` on :3308; the library's data can be imported.
