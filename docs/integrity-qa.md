# Academic Integrity Suite — Manual QA Script

Run this after any change to the Integrity Suite, and once per deploy before announcing
it to instructors. Needs: an `Instructor` (Spatie role `TRAINER`) account, an `Admin`
(`ADMIN`/`SUPERADMIN`) account, and a `Student` account, plus a real `.docx` file and a
running queue worker (`php artisan queue:work database --queue=integrity,default`).

If you don't have real accounts handy, `php artisan db:seed --class=IntegrityDemoSeeder`
creates `demo-admin@sits.edu.et`, `demo-instructor-a@sits.edu.et`, and
`demo-instructor-b@sits.edu.et` (all with the seeded default password) plus 6 documents
spanning every status.

## 1. Upload a DOCX → report completes → heatmap renders

1. Log in as the instructor. Go to **Academic Integrity → Dashboard** (`/integrity`).
2. Click **New Analysis** → **Upload file** → pick a real `.docx` (a few paragraphs, at
   least 150 words — shorter documents short-circuit to `insufficient_text`).
3. Fill in a title, submit. You should land on the report page with a "Analysis in
   progress" spinner.
4. Make sure a queue worker is running (`--queue=integrity,default`) and wait — the page
   polls itself automatically, no manual refresh needed.
5. **Pass criteria:** status becomes `complete`; the gauge shows a 0–100% score with a
   verdict label and confidence badge; the **Sentence Heatmap** renders the *original*
   document text with per-sentence background shading (transparent → amber → red); the
   **Statistical Signals** panel shows all 11 signals with meters and plain-English
   explanations; the disclaimer banner is visible and does not have a dismiss button.

## 2. Run plagiarism vs. a seeded copy

1. Take the same DOCX from step 1 (or any other analyzed document's text), copy roughly
   the first third of it, and paste it into a **second** new analysis (mix in some new
   original text after the copied portion so it isn't 100% identical).
2. Open the second document's report → **Plagiarism** tab → **Check Internal Corpus**.
3. **Pass criteria:** `overall_similarity` lands in a plausible range for a partial
   copy (roughly proportional to how much you copied — copying ~30% of the source should
   land around 25–40%, not near 0% or 100%); the match list shows the first document as a
   source; **Shared Passages** shows side-by-side excerpts that are genuinely the copied
   text, correctly aligned (no truncated/mid-word text).
4. Optional: click **Check Published Sources** to exercise the web-search path (costs
   real Claude API calls, counts 5× against the daily quota — only do this if
   `CLAUDE_PRO_API_KEY` is configured and you intend to spend the quota).

## 3. Export PDF

1. On a completed report, click **Export PDF** in the header (next to **Reanalyze**).
2. **Pass criteria:** a PDF downloads and opens cleanly; it contains the disclaimer text
   verbatim, the AI Detection Score section, the Statistical Signals table, the Sentence
   Heatmap (highlighted, not garbled — check for stray mid-word splits, which would
   indicate a sentence-offset bug), the Plagiarism section if a plagiarism check ran, and
   the Review Status section.
3. Check `integrity_audit_log` for a new `exported` row against this report.

## 4. Student role is blocked everywhere

1. Log out, log in as the `Student` account.
2. Try, in order: `/integrity`, `/integrity/history`, and the report URL from step 1
   directly (`/integrity/documents/{uuid}`).
3. **Pass criteria:** every one of these returns a 403 (Laravel's standard forbidden
   page) — the student never sees a score, a document title, or any Integrity Suite UI
   at all, not even a redirect that hints the module exists.
4. As a bonus check: log in as `Admin` and confirm they *can* open a document created by
   an instructor other than themselves (ownership scoping applies to instructors, not
   admins) — and that logging in as a *different* instructor cannot open it (403).

## 5. Review workflow sanity check

1. As the instructor who owns a flagged report, click **Start Review**, add a note, then
   **Clear** (or **Uphold**).
2. **Pass criteria:** the badge updates immediately; re-running
   `PATCH /integrity/reports/{id}/review` with an out-of-order action (e.g. trying to
   uphold a report that's still in `none`) returns 422, not a silent no-op.
3. Run `php artisan integrity:recalibrate` — with at least 5 `cleared` reports in the DB
   it should print new baseline mean/stddev per signal and exit 0; with fewer than 5 it
   should refuse and exit 1 without touching anything.
