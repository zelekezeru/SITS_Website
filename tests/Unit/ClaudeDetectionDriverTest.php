<?php

namespace Tests\Unit;

use App\Services\Integrity\Detection\ClaudeDetectionDriver;
use Tests\TestCase;

class ClaudeDetectionDriverTest extends TestCase
{
    private function fakeMessage(array $contentBlocks, int $inputTokens = 200, int $outputTokens = 100): object
    {
        return (object) [
            'content' => array_map(fn (array $b) => (object) $b, $contentBlocks),
            'usage' => (object) ['inputTokens' => $inputTokens, 'outputTokens' => $outputTokens],
        ];
    }

    public function test_parses_a_likely_ai_assessment(): void
    {
        $message = $this->fakeMessage([
            ['type' => 'tool_use', 'name' => 'submit_detection_analysis', 'input' => [
                'overall_assessment' => 'likely_ai',
                'probability' => 88,
                'confidence' => 'high',
                'reasoning_summary' => 'Uniform sentence rhythm and generic transitions throughout.',
                'sentence_flags' => [['index' => 2, 'reason' => 'generic transition']],
                'style_observations' => ['formulaic structure'],
            ]],
        ]);

        $result = ClaudeDetectionDriver::parseAnalysisResult($message);

        $this->assertTrue($result['success']);
        $this->assertSame('likely_ai', $result['overall_assessment']);
        $this->assertSame(88, $result['probability']);
        $this->assertSame('high', $result['confidence']);
        $this->assertCount(1, $result['sentence_flags']);
        $this->assertSame(300, $result['tokens_used']);
    }

    public function test_fails_gracefully_when_claude_never_calls_the_tool(): void
    {
        $message = $this->fakeMessage([
            ['type' => 'text', 'text' => 'I refuse to answer.'],
        ]);

        $result = ClaudeDetectionDriver::parseAnalysisResult($message);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_analyze_uses_the_parsed_result_from_a_mocked_call(): void
    {
        $fakeMessage = $this->fakeMessage([
            ['type' => 'tool_use', 'name' => 'submit_detection_analysis', 'input' => [
                'overall_assessment' => 'likely_human',
                'probability' => 12,
                'confidence' => 'medium',
                'reasoning_summary' => 'Varied voice and idiosyncratic phrasing.',
                'sentence_flags' => [],
                'style_observations' => [],
            ]],
        ]);

        $driver = \Mockery::mock(ClaudeDetectionDriver::class.'[callClaude]');
        $driver->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('callClaude')->once()->andReturn($fakeMessage);

        $result = $driver->analyze('Some human-written text.');

        $this->assertTrue($result['success']);
        $this->assertSame('likely_human', $result['overall_assessment']);
        $this->assertSame(12, $result['probability']);
    }

    public function test_analyze_returns_a_graceful_error_when_the_api_call_throws(): void
    {
        $driver = \Mockery::mock(ClaudeDetectionDriver::class.'[callClaude]');
        $driver->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('callClaude')->once()->andThrow(new \RuntimeException('network down'));

        $result = $driver->analyze('Some text.');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_is_unavailable_without_an_api_key(): void
    {
        config(['ai.providers.claude_pro.api_key' => '']);

        $driver = new ClaudeDetectionDriver;

        $this->assertFalse($driver->isAvailable());
    }
}
