<?php

namespace Database\Factories;

use App\Enums\WritingReportType;
use App\Models\IntegrityDocument;
use App\Models\WritingReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WritingReport>
 */
class WritingReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'integrity_document_id' => IntegrityDocument::factory(),
            'type' => WritingReportType::SUMMARY,
            'payload' => [],
            'model' => 'claude-opus-5',
            'token_usage' => ['input' => 1200, 'output' => 300],
        ];
    }
}
