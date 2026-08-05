<?php

namespace App\Console\Commands;

use App\Models\IntegrityReport;
use App\Models\User;
use App\Models\WritingReport;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Cost oversight: per-instructor Claude token usage for a given month,
 * summed across AI-detection passes (integrity_reports.claude_analysis)
 * and writing tools (writing_reports.token_usage).
 */
class IntegrityUsageReport extends Command
{
    protected $signature = 'integrity:usage-report {month : YYYY-MM}';

    protected $description = 'Print per-instructor Claude token usage for the integrity suite in a given month';

    public function handle(): int
    {
        try {
            $start = Carbon::createFromFormat('Y-m', $this->argument('month'))->startOfMonth();
        } catch (\Throwable) {
            $this->error("Invalid month format — expected YYYY-MM, got '{$this->argument('month')}'.");

            return self::FAILURE;
        }
        $end = $start->copy()->endOfMonth();

        $usage = [];

        IntegrityReport::whereBetween('analyzed_at', [$start, $end])
            ->with('document')
            ->get()
            ->each(function (IntegrityReport $report) use (&$usage) {
                $instructorId = $report->document?->instructor_id;
                if (! $instructorId) {
                    return;
                }
                $usage[$instructorId] = ($usage[$instructorId] ?? 0) + (int) ($report->claude_analysis['tokens_used'] ?? 0);
            });

        WritingReport::whereBetween('created_at', [$start, $end])
            ->with('document')
            ->get()
            ->each(function (WritingReport $writingReport) use (&$usage) {
                $instructorId = $writingReport->document?->instructor_id;
                if (! $instructorId) {
                    return;
                }
                $usage[$instructorId] = ($usage[$instructorId] ?? 0) + (int) ($writingReport->token_usage['tokens_used'] ?? 0);
            });

        if (empty($usage)) {
            $this->info("No Claude usage recorded for {$this->argument('month')}.");

            return self::SUCCESS;
        }

        arsort($usage);

        $rows = [];
        foreach ($usage as $instructorId => $tokens) {
            $rows[] = [User::find($instructorId)?->name ?? "User #{$instructorId}", number_format($tokens)];
        }

        $this->table(['Instructor', 'Tokens Used'], $rows);
        $this->info(sprintf('Total: %s tokens across %d instructor(s).', number_format(array_sum($usage)), count($usage)));

        return self::SUCCESS;
    }
}
