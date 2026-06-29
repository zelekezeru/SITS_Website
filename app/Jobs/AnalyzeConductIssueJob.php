<?php

namespace App\Jobs;

use App\Models\ConductIssue;
use App\Models\AiAnalysis;
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

            return;
        }

        // Store analysis result
        AiAnalysis::create([
            'analysable_type' => ConductIssue::class,
            'analysable_id' => $this->conductIssue->id,
            'analysis_type' => 'conduct_assessment',
            'result' => $result,
            'provider' => config('ai.default'),
            'tokens_used' => 0, // TODO: track actual token usage
        ]);

        Log::info('Conduct issue analyzed successfully', [
            'conduct_issue_id' => $this->conductIssue->id,
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
