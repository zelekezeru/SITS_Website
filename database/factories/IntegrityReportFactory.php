<?php

namespace Database\Factories;

use App\Enums\IntegrityConfidence;
use App\Enums\IntegrityReviewStatus;
use App\Enums\IntegrityVerdict;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrityReport>
 */
class IntegrityReportFactory extends Factory
{
    public function definition(): array
    {
        $probability = fake()->numberBetween(0, 100);

        return [
            'integrity_document_id' => IntegrityDocument::factory(),
            'ai_probability' => $probability,
            'confidence' => IntegrityConfidence::MEDIUM,
            'verdict_label' => $probability >= 70 ? IntegrityVerdict::LIKELY_AI : IntegrityVerdict::LIKELY_HUMAN,
            'statistical_signals' => [],
            'claude_analysis' => [],
            'sentence_scores' => [],
            'flagged' => $probability >= 70,
            'review_status' => IntegrityReviewStatus::NONE,
            'engine_version' => '1.0.0',
            'analyzed_at' => now(),
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn () => [
            'ai_probability' => fake()->numberBetween(70, 100),
            'verdict_label' => IntegrityVerdict::LIKELY_AI,
            'flagged' => true,
        ]);
    }
}
