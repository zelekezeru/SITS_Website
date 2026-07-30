<?php

namespace App\Jobs;

use App\Models\ConductIssue;
use App\Models\ConductIssueAnalysis;
use App\Models\Setting;
use App\Services\Ai\AiServiceManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeConductIssueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    protected $conductIssue;

    public function __construct(ConductIssue $conductIssue)
    {
        $this->conductIssue = $conductIssue;
    }

    public function handle()
    {
        $manager = new AiServiceManager();

        if (!$manager->isEnabled()) {
            Log::info('AI service disabled, skipping analysis');

            return;
        }

        // Perform analysis
        $result = $manager->analyzeConductIssue(
            $this->conductIssue->description_en,
            $this->conductIssue->issue_type->value,
            $this->conductIssue->severity->value
        );

        if (!$result['success']) {
            Log::error('Conduct issue analysis failed', [
                'conduct_issue_id' => $this->conductIssue->id,
                'error' => $result['error'],
            ]);

            $this->fail(new \RuntimeException($result['error']));
            return;
        }

        $provider = $result['provider'] ?? Setting::get('ai_default_provider', config('ai.default', 'claude_pro'));
        $model    = $result['model'] ?? Setting::get("{$provider}_model", config("ai.providers.{$provider}.model", ''));

        ConductIssueAnalysis::updateOrCreate(
            [
                'conduct_issue_id' => $this->conductIssue->id,
                'provider'         => $provider,
            ],
            [
                'model'                   => $model,
                'severity_assessment'     => $result['severity_assessment']    ?? null,
                'confidence'              => $result['confidence']             ?? null,
                'risk_level'              => $result['risk_level']             ?? null,
                'suggested_actions'       => $result['suggested_actions']      ?? [],
                'escalation_needed'       => $result['escalation_needed']      ?? false,
                'investigation_required'  => $result['investigation_required'] ?? false,
                'warnings'                => $result['warnings']               ?? [],
                'human_confirmed'         => false,
            ]
        );

        Log::info('Conduct issue analyzed successfully', [
            'conduct_issue_id' => $this->conductIssue->id,
            'provider'         => $provider,
        ]);
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Conduct issue analysis job failed', [
            'conduct_issue_id' => $this->conductIssue->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
