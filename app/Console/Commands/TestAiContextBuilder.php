<?php

namespace App\Console\Commands;

use App\Models\Evaluation;
use App\Services\Ai\PerformanceContextBuilder;
use Illuminate\Console\Command;

class TestAiContextBuilder extends Command
{
    protected $signature   = 'ai:test-context';
    protected $description = 'Smoke-test the PerformanceContextBuilder';

    public function handle(): int
    {
        $eval = Evaluation::with([
            'employee.position',
            'employee.department',
            'period',
            'gradeBand',
            'ratings.kpi',
            'incrementRecommendation',
        ])->first();

        if (!$eval) {
            $this->warn('No evaluations in DB — schema is fine, nothing to analyse.');
            return 0;
        }

        $this->info('--- Context Builder Dry-run ---');
        $ctx = (new PerformanceContextBuilder)->build($eval);

        $this->info('Context top-level keys: ' . implode(', ', array_keys($ctx)));
        $this->line('Employee:    ' . ($ctx['employee']['full_name_en'] ?? 'N/A'));
        $this->line('Period:      ' . ($ctx['period']['name'] ?? 'N/A'));
        $this->line('KPI ratings: ' . count($ctx['kpi_ratings']));
        $this->line('Tasks:       ' . count($ctx['tasks']));
        $this->line('Attendance:  ' . (empty($ctx['attendance']) ? 'none' : json_encode($ctx['attendance'])));
        $this->line('Narrative:   ' . (empty($ctx['narrative']) ? 'none' : 'body_len=' . strlen($ctx['narrative']['body'] ?? '')));
        $this->line('Increment:   ' . (empty($ctx['increment']) ? 'none' : json_encode($ctx['increment'])));
        $this->info('Dry-run PASSED.');

        $this->info("\n--- AI Service Manager Test ---");
        $manager = new \App\Services\Ai\AiServiceManager();
        if (!$manager->isEnabled()) {
            $this->error('AI Service is disabled. Set AI_ENABLED=true in .env');
            return 1;
        }

        $this->info('AI Service Enabled: YES');
        $available = $manager->getAvailableProviders();
        $this->info('Available Providers: ' . implode(', ', array_keys($available)));

        // Test narrative analysis
        $report = \App\Models\NarrativeReport::first();
        if ($report) {
            $this->info("\nTesting Narrative Analysis Job...");
            \App\Jobs\AnalyzeNarrativeReportJob::dispatchSync($report);
            $analysis = \App\Models\AiAnalysis::where('narrative_report_id', $report->id)->first();
            if ($analysis) {
                $this->info('Narrative analysis created successfully!');
                $this->line('Summary EN: ' . $analysis->summary_en);
                $this->line('Sentiment:  ' . json_encode($analysis->sentiment));
            } else {
                $this->error('Narrative analysis was not created.');
            }
        } else {
            $this->warn('No narrative report in DB to test.');
        }

        // Test performance insight analysis
        $this->info("\nTesting Performance Insight Job...");
        \App\Jobs\GeneratePerformanceInsightJob::dispatchSync($eval);
        
        // Find the newly created/updated performance analysis
        $targetReport = \App\Models\NarrativeReport::where('employee_id', $eval->employee_id)
            ->where('evaluation_period_id', $eval->evaluation_period_id)
            ->latest()->first();
            
        if ($targetReport) {
            $performanceAnalysis = \App\Models\AiAnalysis::where('narrative_report_id', $targetReport->id)
                ->where('provider', '!=', 'claude_pro') // Or any provider used
                ->orWhere('narrative_report_id', $targetReport->id)
                ->first();
                
            if ($performanceAnalysis) {
                $this->info('Performance analysis created successfully!');
                $this->line('Summary EN: ' . $performanceAnalysis->summary_en);
                $this->line('Risk flags: ' . json_encode($performanceAnalysis->risk_flags));
            } else {
                $this->error('Performance analysis was not created.');
            }
        } else {
            $this->error('Failed to resolve narrative report anchor.');
        }

        return 0;
    }
}
