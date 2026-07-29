<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// cPanel has no persistent queue:work daemon (no supervisor on shared hosting),
// so the cron-driven scheduler (`* * * * * php artisan schedule:run`, see
// docs/deploy-to-cpanel.md STEP 6) drives the queue instead: each minute it
// runs the worker for up to 50s and exits, well inside the 1-minute cron tick.
// This is what actually processes AnalyzeNarrativeReportJob,
// AnalyzeConductIssueJob, and GeneratePerformanceInsightJob.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
