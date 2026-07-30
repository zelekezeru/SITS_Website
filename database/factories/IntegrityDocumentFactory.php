<?php

namespace Database\Factories;

use App\Enums\IntegrityDocumentSource;
use App\Enums\IntegrityDocumentStatus;
use App\Models\IntegrityDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IntegrityDocument>
 */
class IntegrityDocumentFactory extends Factory
{
    public function definition(): array
    {
        $text = fake()->paragraphs(6, true);

        return [
            'instructor_id' => User::factory(),
            'student_id' => null,
            'course_id' => null,
            'title' => fake()->sentence(4),
            'original_filename' => null,
            'mime_type' => null,
            'source' => IntegrityDocumentSource::PASTE,
            'word_count' => str_word_count($text),
            'language' => 'en',
            'extracted_text' => $text,
            'status' => IntegrityDocumentStatus::COMPLETE,
            'failure_reason' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => IntegrityDocumentStatus::PENDING,
            'extracted_text' => null,
            'word_count' => 0,
        ]);
    }

    public function failed(string $reason = 'likely_scanned_pdf_no_ocr'): static
    {
        return $this->state(fn () => [
            'status' => IntegrityDocumentStatus::FAILED,
            'failure_reason' => $reason,
        ]);
    }
}
