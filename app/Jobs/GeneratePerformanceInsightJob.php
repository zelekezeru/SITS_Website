<?php

namespace App\Jobs;

use App\Models\Evaluation;
use App\Models\NarrativeReport;
use App\Models\AiAnalysis;
use App\Models\Setting;
use App\Services\Ai\AiServiceManager;
use App\Services\Ai\PerformanceContextBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * GeneratePerformanceInsightJob
 *
 * Deep-analyses an entire Evaluation using all available parameters
 * (scores, KPI ratings, attendance, tasks, narrative, increment) and
 * persists the result into ai_analyses, linked to the employee's
 * NarrativeReport for the period.
 *
 * If no NarrativeReport exists for the period we create a placeholder
 * so the FK constraint on ai_analyses is satisfied.
 */
class GeneratePerformanceInsightJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Extended thinking can take 60–90 s; allow 3 minutes. */
    public int $tries   = 3;
    public int $timeout = 180;

    public function __construct(protected Evaluation $evaluation)
    {
    }

    public function handle(AiServiceManager $manager, PerformanceContextBuilder $builder): void
    {
        if (!$manager->isEnabled()) {
            Log::info('AI service disabled, skipping performance insight generation');
            return;
        }

        // ------------------------------------------------------------------
        // 1. Build the full context
        // ------------------------------------------------------------------
        $context = $builder->build($this->evaluation);

        // ------------------------------------------------------------------
        // 2. Call the AI
        // ------------------------------------------------------------------
        $result = $manager->analyzePerformance($context);

        if (!$result['success']) {
            Log::error('Performance insight generation failed', [
                'evaluation_id' => $this->evaluation->id,
                'error'         => $result['error'],
            ]);
            $this->fail(new \RuntimeException($result['error']));
            return;
        }

        // ------------------------------------------------------------------
        // 3. Resolve (or create) the NarrativeReport anchor
        // ------------------------------------------------------------------
        $report = $this->resolveNarrativeReport();

        // ------------------------------------------------------------------
        // 4. Persist into ai_analyses (upsert so re-runs overwrite)
        // ------------------------------------------------------------------
        $provider = $result['provider'] ?? Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
        $model    = $result['model'] ?? Setting::get("{$provider}_model", config("ai.providers.{$provider}.model", ''));

        AiAnalysis::updateOrCreate(
            [
                'narrative_report_id' => $report->id,
                'provider'            => $provider,
            ],
            [
                'model'           => $model,
                'summary_en'      => $result['summary_en']               ?? null,
                'summary_am'      => $result['summary_am']               ?? null,
                'kpi_scores_json' => $result['kpi_scores_json']          ?? [],
                'sentiment'       => $result['sentiment']                ?? [],
                'risk_flags'      => $this->buildRiskFlagsPayload($result),
                'human_confirmed' => false,
            ]
        );

        Log::info('Performance insight generated and stored', [
            'evaluation_id'        => $this->evaluation->id,
            'narrative_report_id'  => $report->id,
            'provider'             => $provider,
            'tokens_used'          => $result['tokens_used'] ?? 0,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Performance insight job permanently failed', [
            'evaluation_id' => $this->evaluation->id,
            'error'         => $exception->getMessage(),
        ]);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Find the NarrativeReport for this employee+period, or create a
     * system-generated placeholder so the ai_analyses FK is satisfied.
     */
    protected function resolveNarrativeReport(): NarrativeReport
    {
        $employeeId = $this->evaluation->employee_id;
        $periodId   = $this->evaluation->evaluation_period_id;

        $report = NarrativeReport::where('employee_id', $employeeId)
            ->where('evaluation_period_id', $periodId)
            ->orderByDesc('created_at')
            ->first();

        if (!$report) {
            $report = NarrativeReport::create([
                'employee_id'          => $employeeId,
                'evaluation_period_id' => $periodId,
                'language'             => 'en',
                'body'                 => '[AI-generated performance insight — no manual narrative submitted]',
            ]);
        }

        return $report;
    }

    /**
     * Merge risk_flags + strengths + development_areas + interventions
     * into the risk_flags JSON column so the UI can surface everything.
     */
    protected function buildRiskFlagsPayload(array $result): array
    {
        $flags = $result['risk_flags']    ?? [];

        // Tag strengths and development areas for easy parsing in the UI
        foreach ($result['strengths'] ?? [] as $s) {
            $flags[] = '[STRENGTH] ' . $s;
        }
        foreach ($result['development_areas'] ?? [] as $d) {
            $flags[] = '[DEVELOP] ' . $d;
        }

        return $flags;
    }
}
