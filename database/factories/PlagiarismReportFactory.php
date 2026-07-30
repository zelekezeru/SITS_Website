<?php

namespace Database\Factories;

use App\Models\IntegrityDocument;
use App\Models\PlagiarismReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlagiarismReport>
 */
class PlagiarismReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'integrity_document_id' => IntegrityDocument::factory(),
            'overall_similarity' => fake()->numberBetween(0, 40),
            'matches' => [],
            'corpus_size' => fake()->numberBetween(0, 500),
            'analyzed_at' => now(),
        ];
    }
}
