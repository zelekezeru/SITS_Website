<?php

namespace App\Jobs;

use App\Models\NarrativeReport;
use App\Models\AiAnalysis;
use App\Models\Setting;
use App\Services\Ai\AiServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeNarrativeReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(protected NarrativeReport $narrativeReport)
    {
    }

    public function handle(): void
    {
        $manager = new AiServiceManager();

        if (!$manager->isEnabled()) {
            Log::info('AI service disabled, skipping narrative analysis');
            return;
        }

        // ----------------------------------------------------------------
        // BUG FIX: the column is `body`, not `content`
        // ----------------------------------------------------------------
        $result = $manager->analyzeNarrative($this->narrativeReport->body);

        if (!$result['success']) {
            Log::error('Narrative analysis failed', [
                'narrative_report_id' => $this->narrativeReport->id,
                'error'               => $result['error'],
            ]);
            $this->fail(new \RuntimeException($result['error']));
            return;
        }

        $provider = $result['provider'] ?? Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
        $model    = $result['model'] ?? Setting::get("{$provider}_model", config("ai.providers.{$provider}.model", ''));

        // ----------------------------------------------------------------
        // BUG FIX: use the real ai_analyses schema
        // (narrative_report_id, provider, model, summary_en, summary_am,
        //  kpi_scores_json, sentiment, risk_flags, human_confirmed)
        // ----------------------------------------------------------------
        AiAnalysis::updateOrCreate(
            [
                'narrative_report_id' => $this->narrativeReport->id,
                'provider'            => $provider,
            ],
            [
                'model'          => $model,
                'summary_en'     => $result['summary_en']            ?? null,
                'summary_am'     => $result['summary_am']            ?? null,
                'kpi_scores_json'=> $result['kpi_scores']            ?? [],
                'sentiment'      => $result['sentiment']             ?? [],
                'risk_flags'     => array_merge(
                    $result['risk_flags']            ?? [],
                    $result['strengths']             ?? [],
                    $result['areas_for_improvement'] ?? []
                ),
                'human_confirmed'=> false,
            ]
        );

        Log::info('Narrative report analyzed and stored', [
            'narrative_report_id' => $this->narrativeReport->id,
            'provider'            => $provider,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Narrative report analysis job permanently failed', [
            'narrative_report_id' => $this->narrativeReport->id,
            'error'               => $exception->getMessage(),
        ]);
    }
}
