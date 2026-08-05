<?php

namespace Tests\Feature;

use App\Enums\IntegrityReviewStatus;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\Setting;
use App\Services\Integrity\Detection\StatisticalAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalibrateIntegrityBaselinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_aborts_when_fewer_than_five_cleared_documents_exist(): void
    {
        IntegrityDocument::factory()->count(2)->create()->each(function ($document) {
            IntegrityReport::factory()->for($document, 'document')->create([
                'review_status' => IntegrityReviewStatus::CLEARED,
            ]);
        });

        $this->artisan('integrity:recalibrate')
            ->expectsOutputToContain('need at least 5')
            ->assertExitCode(1);

        $this->assertNull(Setting::get('integrity_baseline_burstiness_mean'));
    }

    public function test_recalibrates_baselines_from_cleared_documents_only(): void
    {
        // 5 cleared, human-authored-looking documents — should count.
        for ($i = 0; $i < 5; $i++) {
            $document = IntegrityDocument::factory()->create([
                'extracted_text' => "I really wasn't sure about this one, honestly. My take kept changing "
                    ."as I read more, and — annoyingly — I still don't have a tidy answer. Draft {$i}.",
            ]);
            IntegrityReport::factory()->for($document, 'document')->create([
                'review_status' => IntegrityReviewStatus::CLEARED,
            ]);
        }

        // A flagged, unreviewed document — must NOT feed the recalibration.
        $flagged = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('Furthermore, moreover. ', 50)]);
        IntegrityReport::factory()->for($flagged, 'document')->create(['review_status' => IntegrityReviewStatus::NONE]);

        $this->artisan('integrity:recalibrate')->assertExitCode(0);

        $this->assertNotNull(Setting::get('integrity_baseline_burstiness_mean'));
        $this->assertNotNull(Setting::get('integrity_baseline_burstiness_stddev'));
    }

    public function test_statistical_analyzer_uses_recalibrated_baseline_over_config_default(): void
    {
        Setting::set('integrity_baseline_burstiness_mean', 999.0, 'integrity', 'decimal');
        Setting::set('integrity_baseline_burstiness_stddev', 1.0, 'integrity', 'decimal');

        $result = (new StatisticalAnalyzer)->analyze('One sentence here. A second one follows now. Third and final one.');

        // With mean pinned absurdly high, any real burstiness value sits far below it.
        $this->assertLessThan(-5, $result['signals']['burstiness']['zscore_vs_baseline']);
    }
}
