<?php

namespace Database\Factories;

use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CorpusFingerprint>
 */
class CorpusFingerprintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'integrity_document_id' => IntegrityDocument::factory(),
            'shingle_hash' => fake()->numberBetween(1, PHP_INT_MAX),
            'position' => fake()->numberBetween(0, 5000),
        ];
    }
}
