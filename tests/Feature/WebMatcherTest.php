<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Services\Integrity\Plagiarism\ClaudeWebSearchClient;
use App\Services\Integrity\Plagiarism\WebMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WebMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function mockClient(): ClaudeWebSearchClient
    {
        return \Mockery::mock(ClaudeWebSearchClient::class);
    }

    public function test_returns_structured_matches_for_found_passages(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->andReturn([
            'success' => true,
            'found' => true,
            'url' => 'https://example.com/source',
            'source_title' => 'Some Source',
            'matched_excerpt' => 'matched text here',
            'match_quality' => 'exact',
        ]);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'This distinctive sentence has exactly enough words to qualify as a passage candidate here.',
        ]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertGreaterThan(0, $result['passages_checked']);
        $this->assertNotEmpty($result['matches']);
        $this->assertSame('web', $result['matches'][0]['source_type']);
        $this->assertSame('https://example.com/source', $result['matches'][0]['url']);
        $this->assertSame(100, $result['web_similarity']);
    }

    public function test_no_matches_when_claude_reports_nothing_found(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->andReturn(['success' => true, 'found' => false, 'match_quality' => 'none']);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'This distinctive sentence has exactly enough words to qualify as a passage candidate here.',
        ]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertEmpty($result['matches']);
        $this->assertSame(0, $result['web_similarity']);
    }

    public function test_excludes_quoted_passages(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->never();

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => '"This entire sentence is wrapped in quotation marks and should be skipped."',
        ]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertSame(0, $result['passages_checked']);
    }

    public function test_excludes_scripture_citations(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->never();

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'As it says in John 3:16, this whole sentence should be excluded from web checks.',
        ]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertSame(0, $result['passages_checked']);
    }

    public function test_checks_a_non_quoted_non_scripture_sentence_alongside_excluded_ones(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->once()->andReturn(['success' => true, 'found' => false, 'match_quality' => 'none']);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'As it says in John 3:16, God loved the world. '
                .'"This part is a direct quotation from somewhere else entirely." '
                .'This final sentence is original phrasing distinctive enough to check against the web.',
        ]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertSame(1, $result['passages_checked']);
    }

    public function test_caps_at_twelve_passages(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->times(12)->andReturn(['success' => true, 'found' => false, 'match_quality' => 'none']);

        // Distinct sentences (not repeats) so the cache doesn't collapse them to one call.
        $sentences = [];
        for ($i = 0; $i < 20; $i++) {
            $sentences[] = "This is distinctive candidate sentence number {$i} with plenty of qualifying words.";
        }

        $document = IntegrityDocument::factory()->create(['extracted_text' => implode(' ', $sentences)]);

        $result = (new WebMatcher($client))->check($document);

        $this->assertSame(12, $result['passages_checked']);
    }

    public function test_respects_the_cache_and_does_not_call_claude_twice_for_the_same_passage(): void
    {
        Cache::flush();

        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->once()->andReturn(['success' => true, 'found' => true, 'match_quality' => 'close', 'url' => 'https://x.test']);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'This exact distinctive sentence appears only once but gets checked twice via cache.',
        ]);

        $matcher = new WebMatcher($client);
        $first = $matcher->check($document);
        $second = $matcher->check($document);

        $this->assertSame($first, $second);
    }

    public function test_check_and_persist_preserves_prior_corpus_matches(): void
    {
        $client = $this->mockClient();
        $client->shouldReceive('checkPassage')->andReturn(['success' => true, 'found' => true, 'match_quality' => 'exact', 'url' => 'https://x.test']);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'This distinctive sentence has exactly enough words to qualify as a passage candidate here.',
        ]);

        $document->plagiarismReports()->create([
            'overall_similarity' => 42,
            'matches' => [['source_type' => 'corpus', 'matched_document_id' => 999, 'similarity_pct' => 42]],
            'corpus_size' => 10,
            'analyzed_at' => now(),
        ]);

        $report = (new WebMatcher($client))->checkAndPersist($document);

        $this->assertSame(42, $report->overall_similarity);
        $this->assertNotNull($report->web_similarity);
        $sourceTypes = array_column($report->matches, 'source_type');
        $this->assertContains('corpus', $sourceTypes);
        $this->assertContains('web', $sourceTypes);
    }
}
