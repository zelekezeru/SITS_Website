<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Models\User;
use App\Services\Integrity\Plagiarism\CorpusMatcher;
use App\Services\Integrity\Plagiarism\Fingerprinter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorpusMatcherTest extends TestCase
{
    use RefreshDatabase;

    private Fingerprinter $fingerprinter;

    private CorpusMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fingerprinter = new Fingerprinter;
        $this->matcher = new CorpusMatcher($this->fingerprinter);
    }

    /** 300 distinct, non-repeating words so shingles are unique throughout. */
    private function distinctWords(int $count, string $prefix = 'alpha'): string
    {
        $words = [];
        for ($i = 0; $i < $count; $i++) {
            $words[] = $prefix.$i;
        }

        return implode(' ', $words);
    }

    public function test_a_document_copying_30_percent_of_another_scores_within_expected_range_and_exposes_shared_passages(): void
    {
        $sourceText = $this->distinctWords(300, 'source');
        $docA = IntegrityDocument::factory()->create(['extracted_text' => $sourceText]);
        $this->fingerprinter->fingerprint($docA);

        $sourceWords = explode(' ', $sourceText);
        $copiedPortion = implode(' ', array_slice($sourceWords, 0, 90)); // first 30% of 300 words
        $originalPortion = $this->distinctWords(210, 'unique');
        $docB = IntegrityDocument::factory()->create(['extracted_text' => $copiedPortion.' '.$originalPortion]);
        $this->fingerprinter->fingerprint($docB);

        $result = $this->matcher->match($docB);

        $this->assertGreaterThanOrEqual(25, $result['overall_similarity']);
        $this->assertLessThanOrEqual(40, $result['overall_similarity']);

        $this->assertCount(1, $result['matches']);
        $match = $result['matches'][0];
        $this->assertSame($docA->id, $match['matched_document_id']);
        $this->assertSame('corpus', $match['source_type']);
        $this->assertNotEmpty($match['shared_passages']);

        $passage = $match['shared_passages'][0];
        $this->assertStringContainsString('source0', $passage['source_excerpt']);
        $this->assertNotNull($passage['matched_excerpt']);
    }

    public function test_unrelated_documents_score_below_5_percent(): void
    {
        $docA = IntegrityDocument::factory()->create(['extracted_text' => $this->distinctWords(300, 'aaa')]);
        $docB = IntegrityDocument::factory()->create(['extracted_text' => $this->distinctWords(300, 'zzz')]);

        $this->fingerprinter->fingerprint($docA);
        $this->fingerprinter->fingerprint($docB);

        $result = $this->matcher->match($docB);

        $this->assertLessThan(5, $result['overall_similarity']);
        $this->assertEmpty($result['matches']);
    }

    public function test_excludes_the_same_students_own_earlier_drafts_by_default(): void
    {
        $student = User::factory()->create();
        $text = $this->distinctWords(300, 'draft');

        $earlierDraft = IntegrityDocument::factory()->create(['student_id' => $student->id, 'extracted_text' => $text]);
        $this->fingerprinter->fingerprint($earlierDraft);

        $finalSubmission = IntegrityDocument::factory()->create(['student_id' => $student->id, 'extracted_text' => $text]);
        $this->fingerprinter->fingerprint($finalSubmission);

        $result = $this->matcher->match($finalSubmission);
        $this->assertEmpty($result['matches'], 'own earlier draft must be excluded by default');

        $resultWithSelf = $this->matcher->match($finalSubmission, includeSelf: true);
        $this->assertNotEmpty($resultWithSelf['matches']);
    }

    public function test_does_not_exclude_other_students_identical_submissions(): void
    {
        $studentA = User::factory()->create();
        $studentB = User::factory()->create();
        $text = $this->distinctWords(300, 'shared');

        $docA = IntegrityDocument::factory()->create(['student_id' => $studentA->id, 'extracted_text' => $text]);
        $this->fingerprinter->fingerprint($docA);

        $docB = IntegrityDocument::factory()->create(['student_id' => $studentB->id, 'extracted_text' => $text]);
        $this->fingerprinter->fingerprint($docB);

        $result = $this->matcher->match($docB);

        $this->assertNotEmpty($result['matches']);
        $this->assertSame(100, $result['overall_similarity']);
    }

    public function test_match_and_persist_creates_a_plagiarism_report(): void
    {
        $docA = IntegrityDocument::factory()->create(['extracted_text' => $this->distinctWords(300, 'persist')]);
        $this->fingerprinter->fingerprint($docA);

        $docB = IntegrityDocument::factory()->create(['extracted_text' => $this->distinctWords(300, 'persist')]);
        $this->fingerprinter->fingerprint($docB);

        $report = $this->matcher->matchAndPersist($docB);

        $this->assertSame($docB->id, $report->integrity_document_id);
        $this->assertSame(100, $report->overall_similarity);
        $this->assertNotEmpty($report->matches);
    }
}
