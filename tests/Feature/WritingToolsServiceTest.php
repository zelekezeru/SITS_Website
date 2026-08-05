<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Services\Integrity\Writing\WritingToolsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WritingToolsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.providers.claude_pro.api_key' => 'test-key']);
    }

    private function fakeMessage(string $toolName, array $input, int $inputTokens = 300, int $outputTokens = 100): object
    {
        return (object) [
            'content' => [(object) ['type' => 'tool_use', 'name' => $toolName, 'input' => $input]],
            'usage' => (object) ['inputTokens' => $inputTokens, 'outputTokens' => $outputTokens],
        ];
    }

    private function malformedMessage(): object
    {
        return (object) [
            'content' => [(object) ['type' => 'text', 'text' => 'I would rather not.']],
            'usage' => (object) ['inputTokens' => 100, 'outputTokens' => 20],
        ];
    }

    private function serviceReturning(object ...$messages): WritingToolsService
    {
        $service = \Mockery::mock(WritingToolsService::class.'[callClaude]');
        $service->shouldAllowMockingProtectedMethods();
        $expectation = $service->shouldReceive('callClaude');
        foreach ($messages as $message) {
            $expectation->andReturn($message);
        }

        return $service;
    }

    public function test_grammar_persists_structured_suggestions(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => 'This paper have some grammar issue in it.']);

        $service = $this->serviceReturning($this->fakeMessage('submit_grammar_suggestions', [
            'suggestions' => [
                ['start' => 10, 'end' => 14, 'original' => 'have', 'suggestion' => 'has', 'category' => 'grammar', 'severity' => 'medium'],
            ],
        ]));

        $result = $service->grammar($document);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['payload']);
        $this->assertSame('has', $result['payload'][0]['suggestion']);
        $this->assertDatabaseHas('writing_reports', ['integrity_document_id' => $document->id, 'type' => 'grammar']);
    }

    public function test_summarize_persists_abstract_claims_and_title(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);

        $service = $this->serviceReturning($this->fakeMessage('submit_summary', [
            'abstract' => 'A concise abstract.',
            'key_claims' => ['Claim one.', 'Claim two.'],
            'suggested_title' => 'A Suggested Title',
        ]));

        $result = $service->summarize($document);

        $this->assertTrue($result['success']);
        $this->assertSame('A Suggested Title', $result['payload']['suggested_title']);
        $this->assertCount(2, $result['payload']['key_claims']);
    }

    public function test_fact_check_marks_claims_advisory_only(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);

        $service = $this->serviceReturning($this->fakeMessage('submit_fact_check', [
            'claims' => [
                ['claim' => 'The council convened in 451 AD.', 'checkability' => 'verifiable', 'note' => 'Historical date, checkable.'],
                ['claim' => 'This was the most important council.', 'checkability' => 'opinion', 'note' => 'Value judgment.'],
            ],
        ]));

        $result = $service->factCheck($document);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['payload']);
        $this->assertSame('verifiable', $result['payload'][0]['checkability']);
        // Advisory only — nothing in the shape asserts truth/falsehood.
        $this->assertArrayNotHasKey('is_true', $result['payload'][0]);
    }

    public function test_feedback_assistant_drafts_pastoral_feedback_incorporating_instructor_notes(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);
        $report = IntegrityReport::factory()->flagged()->for($document, 'document')->create();

        $service = $this->serviceReturning($this->fakeMessage('submit_feedback_draft', [
            'draft' => 'Thanks for your submission — I noticed a few things worth discussing together.',
        ]));

        $result = $service->feedbackAssistant($document, $report, 'Seems rushed, ask about their process.');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('discussing', $result['payload']['draft']);
        $this->assertDatabaseHas('writing_reports', ['integrity_document_id' => $document->id, 'type' => 'feedback']);
    }

    public function test_retries_once_on_a_malformed_response_and_succeeds_on_the_second_attempt(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);

        $service = $this->serviceReturning(
            $this->malformedMessage(),
            $this->fakeMessage('submit_summary', ['abstract' => 'ok', 'key_claims' => [], 'suggested_title' => 'ok']),
        );

        $result = $service->summarize($document);

        $this->assertTrue($result['success']);
    }

    public function test_fails_gracefully_when_both_attempts_are_malformed(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);

        $service = $this->serviceReturning($this->malformedMessage(), $this->malformedMessage());

        $result = $service->summarize($document);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertDatabaseMissing('writing_reports', ['integrity_document_id' => $document->id, 'type' => 'summary']);
    }

    public function test_returns_a_graceful_error_without_calling_claude_when_document_has_no_text(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => '']);

        $service = \Mockery::mock(WritingToolsService::class.'[callClaude]');
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('callClaude')->never();

        $result = $service->summarize($document);

        $this->assertFalse($result['success']);
    }
}
