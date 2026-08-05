<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use App\Models\WritingReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityUsageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_no_usage_for_a_month_with_no_activity(): void
    {
        $this->artisan('integrity:usage-report', ['month' => '2020-01'])
            ->expectsOutputToContain('No Claude usage recorded for 2020-01.')
            ->assertExitCode(0);
    }

    public function test_rejects_an_invalid_month_format(): void
    {
        $this->artisan('integrity:usage-report', ['month' => 'not-a-month'])
            ->assertExitCode(1);
    }

    public function test_sums_tokens_across_detection_and_writing_reports_per_instructor(): void
    {
        $instructor = User::factory()->create(['name' => 'Prof. Test']);
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        IntegrityReport::factory()->for($document, 'document')->create([
            'claude_analysis' => ['tokens_used' => 1000],
            'analyzed_at' => now()->startOfMonth()->addDays(2),
        ]);

        WritingReport::factory()->for($document, 'document')->create([
            'token_usage' => ['tokens_used' => 500],
            'created_at' => now()->startOfMonth()->addDays(3),
        ]);

        $month = now()->format('Y-m');

        $this->artisan('integrity:usage-report', ['month' => $month])
            ->expectsOutputToContain('Prof. Test')
            ->expectsOutputToContain('1,500')
            ->assertExitCode(0);
    }
}
