# Academic Integrity Suite

In-house AI-detection, plagiarism, and writing-tools module for instructors, built
entirely on infrastructure already in this repo — no third-party detector service, no
second API key, no new search backend. Available only to `Instructor` (Spatie role
`TRAINER`) and `Admin` (`ADMIN`/`SUPERADMIN`) accounts, mounted at `/integrity`.

## Academic-policy stance (read this first)

Every score this module produces is an **advisory triage signal, never a verdict**.
This is enforced in code, not just UI copy:

- No code path anywhere writes to a grade, enrollment, or student disciplinary record.
  The review workflow (`IntegrityReport.review_status`) only ever changes its own status
  column and an audit-log row — nothing else.
- The report view's disclaimer banner is permanent and has no dismiss control.
- The Claude system prompts (`ClaudeDetectionDriver`, `WritingToolsService`) explicitly
  instruct the model to weigh theological/academic register correctly and to never treat
  non-native (ESL) phrasing as AI evidence — most SITS students are ESL writers, who are
  statistically more likely to be falsely flagged by generic detectors.
- `flagged` requires *both* a high probability *and* non-low confidence
  (`CompositeScorer`) — a single noisy signal can't flag a student on its own.
- There is deliberately no "humanizer" / detector-bypass tool. The Feedback Assistant
  (writing tool `feedback`) exists in its place, and only ever produces a *draft* an
  instructor edits — it is never sent automatically.

## Architecture

```
app/Services/Integrity/
  TextExtractor.php              DOCX/PDF/TXT/paste → normalized text (Phase 2)
  Detection/
    StatisticalAnalyzer.php      11 pure-PHP signals, no API cost (Phase 3)
    ClaudeDetectionDriver.php    Claude structured-output pass (Phase 3)
    CompositeScorer.php          blends both into ai_probability/confidence/verdict
  Plagiarism/
    Fingerprinter.php            8-word shingle hashing → corpus_fingerprints (Phase 4)
    CorpusMatcher.php            shingle-overlap matching against the internal corpus
    ClaudeWebSearchClient.php    low-level Claude web-search primitive (one passage)
    WebMatcher.php               passage selection + caching on top of the client above
  Writing/
    WritingToolsService.php      grammar / summarize / factCheck / feedbackAssistant
  IntegrityQuota.php              per-instructor daily quota

app/Jobs/
  RunIntegrityAnalysis.php        the orchestration job: statistical → Claude → composite
  RunWritingTool.php               thin wrapper dispatching one WritingToolsService method

app/Http/Controllers/Integrity/   thin controllers — validate, authorize, dispatch/call
app/Policies/IntegrityDocumentPolicy.php   ownership-only; Admin sees all
routes/integrity.php              all 10 routes, gated by `can:access-integrity-suite`
resources/js/Pages/Integrity/     Dashboard.vue, Report.vue, History.vue (Vue 3 + Inertia)
```

**Pipeline for one document:** `DocumentController@store` extracts text synchronously
(no original file is retained — only `extracted_text`), creates an `IntegrityDocument`,
and dispatches `RunIntegrityAnalysis` onto the `integrity` queue. That job runs the
statistical pass (always, on the full document — it's free), the Claude pass (chunked
above `chunk_trigger_words`), blends them via `CompositeScorer`, persists an
`IntegrityReport`, and fires `IntegrityReportCompleted` — which a listener uses to
automatically fingerprint the document into the plagiarism corpus. Plagiarism and
writing-tool checks are separate, explicitly-triggered actions (their own queued jobs),
not part of the automatic pipeline.

**Data model:** `integrity_documents` → `integrity_reports` (1:1), `plagiarism_reports`
(1:many, one row updated per corpus/web check), `writing_reports` (1:many, one per tool
type), `corpus_fingerprints` (shingle index), `integrity_audit_log` (append-only:
`analyze`, review transitions, `exported`).

## Config reference (`config/integrity.php`)

| Key | Default | Notes |
|---|---|---|
| `flag_threshold` | 70 | `ai_probability` ≥ this (and confidence ≠ low) → `flagged` |
| `min_words` | 150 | below this, analysis is skipped → verdict `insufficient_text` |
| `max_words` | 30000 | soft ceiling; longer documents still process, just slowly |
| `chunk_trigger_words` | 6000 | above this, the **Claude pass only** is chunked |
| `chunk_words` / `chunk_overlap_words` | 4000 / 200 | chunk size and overlap |
| `weights.statistical` / `weights.claude` | 0.45 / 0.55 | composite score blend, must sum to 1.0 |
| `daily_quota` | 50 | new documents per instructor per day before a 429 |
| `web_check_quota_weight` | 5 | web-source plagiarism check costs this many quota units |
| `web_check_cache_days` | 30 | cache TTL for a given passage's web-search result |
| `claude_model` | `CLAUDE_PRO_MODEL` | override with `INTEGRITY_CLAUDE_MODEL` if ever needed |
| `transition_stoplist` | seeded list | generic-transition phrases for signal #5; edit freely |
| `baselines.*` | seeded per-signal mean/stddev | see recalibration below |

All of the above are read from `env()` with defaults, or are plain array literals safe
to edit directly in `config/integrity.php` (no env var needed for the stoplist/baselines).

## Recalibrating statistical baselines

The 11 statistical signals are scored as z-scores against a baseline mean/stddev per
signal, seeded in `config/integrity.php` for graduate theological writing. As
instructors mark reports `cleared` (confirmed human-written) over time, recompute the
baselines from real data:

```bash
php artisan integrity:recalibrate
```

Requires at least 5 `cleared` reports (refuses and exits 1 otherwise, changing nothing).
Writes overrides via the `Setting` model (`integrity_baseline_{signal}_mean` /
`_stddev`) — `StatisticalAnalyzer` checks these ahead of the config defaults on every
future analysis, so recalibration takes effect immediately without a deploy.

## Cost oversight

```bash
# Fingerprint historical submissions that predate this module
php artisan integrity:backfill-corpus [--course=ID]

# Per-instructor Claude token usage for a given month
php artisan integrity:usage-report 2026-08
```

Every Claude call's `tokens_used` is recorded — on `integrity_reports.claude_analysis`
for detection passes, on `writing_reports.token_usage` for writing tools — so
`usage-report` has real numbers to sum, not estimates.

## Known gaps / deliberately out of scope

- No true drag-and-drop upload (native file input only, matching every other upload
  form already in this codebase).
- The web-source plagiarism check's "distinctive passage" selection is a simple
  heuristic (sentence-length filter + Scripture/quote exclusion), not a true
  statistical-unusualness ranking — a reasonable first pass given no corpus-wide term
  frequency data exists yet.
- Quota is a same-day document count, not a full API-call ledger — the 5× web-check
  weight is checked against that count, not accumulated separately. A dedicated ledger
  table would be a natural follow-up if usage patterns ever demand more precision.
