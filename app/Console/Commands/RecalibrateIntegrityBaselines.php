<?php

namespace App\Console\Commands;

use App\Enums\IntegrityReviewStatus;
use App\Models\IntegrityDocument;
use App\Models\Setting;
use App\Services\Integrity\Detection\StatisticalAnalyzer;
use Illuminate\Console\Command;

/**
 * Recomputes statistical-signal baselines from the corpus of documents
 * instructors have marked `cleared` (human-confirmed human writing).
 * Writes overrides via Setting — StatisticalAnalyzer reads them ahead of
 * the config/integrity.php defaults on every future analysis.
 */
class RecalibrateIntegrityBaselines extends Command
{
    protected $signature = 'integrity:recalibrate';

    protected $description = 'Recompute AI-detection statistical baselines from documents reviewers have marked cleared';

    protected const MIN_DOCUMENTS = 5;

    public function handle(StatisticalAnalyzer $analyzer): int
    {
        $documents = IntegrityDocument::query()
            ->whereHas('report', fn ($q) => $q->where('review_status', IntegrityReviewStatus::CLEARED))
            ->whereNotNull('extracted_text')
            ->get();

        if ($documents->count() < self::MIN_DOCUMENTS) {
            $this->warn(sprintf(
                'Only %d cleared document(s) found — need at least %d for a meaningful recalibration. Aborting.',
                $documents->count(),
                self::MIN_DOCUMENTS
            ));

            return self::FAILURE;
        }

        $rawValuesBySignal = [];
        foreach ($documents as $document) {
            $result = $analyzer->analyze((string) $document->extracted_text);
            foreach ($result['signals'] as $key => $signal) {
                $rawValuesBySignal[$key][] = $signal['value'];
            }
        }

        foreach ($rawValuesBySignal as $key => $values) {
            $mean = array_sum($values) / count($values);
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / count($values);
            $stddev = sqrt($variance) ?: 1.0;

            Setting::set("integrity_baseline_{$key}_mean", round($mean, 4), 'integrity', 'decimal');
            Setting::set("integrity_baseline_{$key}_stddev", round($stddev, 4), 'integrity', 'decimal');

            $this->line(sprintf('%-30s mean=%10.4f  stddev=%10.4f  (n=%d)', $key, $mean, $stddev, count($values)));
        }

        $this->info(sprintf(
            'Recalibrated %d signal baseline(s) from %d cleared document(s).',
            count($rawValuesBySignal),
            $documents->count()
        ));

        return self::SUCCESS;
    }
}
