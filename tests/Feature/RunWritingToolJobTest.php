<?php

namespace Tests\Feature;

use App\Enums\WritingReportType;
use App\Jobs\RunWritingTool;
use App\Models\IntegrityDocument;
use App\Services\Integrity\Writing\WritingToolsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RunWritingToolJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_to_the_correct_service_method_and_persists_a_report(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => str_repeat('word ', 400)]);

        $fakeMessage = (object) [
            'content' => [(object) ['type' => 'tool_use', 'name' => 'submit_summary', 'input' => [
                'abstract' => 'ok', 'key_claims' => ['a'], 'suggested_title' => 'Title',
            ]]],
            'usage' => (object) ['inputTokens' => 100, 'outputTokens' => 50],
        ];

        $service = \Mockery::mock(WritingToolsService::class.'[callClaude]');
        $service->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('callClaude')->andReturn($fakeMessage);
        $this->app->instance(WritingToolsService::class, $service);

        $job = new RunWritingTool($document, WritingReportType::SUMMARY);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseHas('writing_reports', [
            'integrity_document_id' => $document->id,
            'type' => 'summary',
        ]);
    }

    public function test_service_failure_does_not_persist_a_report_and_does_not_crash_the_job(): void
    {
        $document = IntegrityDocument::factory()->create(['extracted_text' => '']);

        $job = new RunWritingTool($document, WritingReportType::SUMMARY);
        $this->app->call([$job, 'handle']);

        $this->assertDatabaseMissing('writing_reports', ['integrity_document_id' => $document->id]);
    }
}
