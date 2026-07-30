<?php

namespace Tests\Unit;

use App\Services\Integrity\Plagiarism\ClaudeWebSearchClient;
use Tests\TestCase;

class ClaudeWebSearchClientTest extends TestCase
{
    private function fakeMessage(array $contentBlocks, int $inputTokens = 100, int $outputTokens = 50): object
    {
        return (object) [
            'content' => array_map(fn (array $b) => (object) $b, $contentBlocks),
            'usage' => (object) ['inputTokens' => $inputTokens, 'outputTokens' => $outputTokens],
        ];
    }

    public function test_parses_a_found_match_from_the_report_match_tool_use_block(): void
    {
        $message = $this->fakeMessage([
            ['type' => 'server_tool_use', 'name' => 'web_search', 'input' => ['query' => 'some passage']],
            ['type' => 'web_search_tool_result', 'content' => []],
            ['type' => 'tool_use', 'name' => 'report_match', 'input' => [
                'found' => true,
                'url' => 'https://example.com/article',
                'source_title' => 'Some Article',
                'matched_excerpt' => 'the exact matching text',
                'match_quality' => 'exact',
            ]],
        ]);

        $result = ClaudeWebSearchClient::parseSearchResult($message);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['found']);
        $this->assertSame('https://example.com/article', $result['url']);
        $this->assertSame('exact', $result['match_quality']);
        $this->assertSame(150, $result['tokens_used']);
    }

    public function test_parses_a_not_found_match(): void
    {
        $message = $this->fakeMessage([
            ['type' => 'tool_use', 'name' => 'report_match', 'input' => [
                'found' => false,
                'match_quality' => 'none',
            ]],
        ]);

        $result = ClaudeWebSearchClient::parseSearchResult($message);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['found']);
        $this->assertNull($result['url']);
        $this->assertSame('none', $result['match_quality']);
    }

    public function test_fails_gracefully_when_claude_never_calls_report_match(): void
    {
        $message = $this->fakeMessage([
            ['type' => 'text', 'text' => 'I could not find anything relevant.'],
        ]);

        $result = ClaudeWebSearchClient::parseSearchResult($message);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_is_unavailable_without_an_api_key(): void
    {
        config(['ai.providers.claude_pro.api_key' => '']);

        $client = new ClaudeWebSearchClient;

        $this->assertFalse($client->isAvailable());
    }
}
