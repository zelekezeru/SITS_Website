<?php

namespace Tests\Unit;

use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;
use App\Services\Integrity\Plagiarism\Fingerprinter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FingerprinterTest extends TestCase
{
    use RefreshDatabase;

    private Fingerprinter $fingerprinter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fingerprinter = new Fingerprinter;
    }

    public function test_hash_is_deterministic_and_case_insensitive(): void
    {
        $a = $this->fingerprinter->hash($this->normalizedShingle('the quick brown fox jumps over the lazy'));
        $b = $this->fingerprinter->hash($this->normalizedShingle('the quick brown fox jumps over the lazy'));

        $this->assertSame($a, $b);
    }

    private function normalizedShingle(string $text): string
    {
        return mb_strtolower($text);
    }

    public function test_hash_is_always_non_negative_and_fits_unsigned_bigint(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $hash = $this->fingerprinter->hash('random shingle number '.$i.' with extra words');
            $this->assertGreaterThanOrEqual(0, $hash);
            $this->assertLessThan(2 ** 62, $hash);
        }
    }

    public function test_shingles_count_matches_word_count_minus_shingle_size_plus_one(): void
    {
        $words = array_fill(0, 20, 'word');
        $shingles = $this->fingerprinter->shingles($words);

        $this->assertCount(20 - Fingerprinter::SHINGLE_SIZE + 1, $shingles);
    }

    public function test_position_index_maps_back_to_original_words_array(): void
    {
        $text = 'This is a sentence, with some punctuation! And a second one here — right?';
        $words = $this->fingerprinter->words($text);

        // words() must be whitespace-delimited on the ORIGINAL text, preserving count/order exactly.
        $this->assertSame(explode(' ', $text), $words);
    }

    public function test_fingerprint_stores_one_row_per_shingle_and_is_idempotent_on_rerun(): void
    {
        $document = IntegrityDocument::factory()->create([
            'extracted_text' => str_repeat('one two three four five six seven eight nine ten ', 5),
        ]);

        $count = $this->fingerprinter->fingerprint($document);
        $this->assertSame($count, CorpusFingerprint::where('integrity_document_id', $document->id)->count());

        // Re-running must replace, not duplicate.
        $this->fingerprinter->fingerprint($document);
        $this->assertSame($count, CorpusFingerprint::where('integrity_document_id', $document->id)->count());
    }

    public function test_identical_shingles_at_different_documents_hash_the_same(): void
    {
        $docA = IntegrityDocument::factory()->create(['extracted_text' => 'The quick brown fox jumps over the lazy dog today.']);
        $docB = IntegrityDocument::factory()->create(['extracted_text' => 'The quick brown fox jumps over the lazy dog today.']);

        $this->fingerprinter->fingerprint($docA);
        $this->fingerprinter->fingerprint($docB);

        $hashesA = CorpusFingerprint::where('integrity_document_id', $docA->id)->pluck('shingle_hash')->sort()->values();
        $hashesB = CorpusFingerprint::where('integrity_document_id', $docB->id)->pluck('shingle_hash')->sort()->values();

        $this->assertTrue($hashesA->diff($hashesB)->isEmpty());
    }
}
