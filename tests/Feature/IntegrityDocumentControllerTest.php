<?php

namespace Tests\Feature;

use App\Enums\IntegrityDocumentStatus;
use App\Enums\IntegrityReviewStatus;
use App\Jobs\RunIntegrityAnalysis;
use App\Jobs\RunWritingTool;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class IntegrityDocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        SpatieRole::findOrCreate('TRAINER');
        $user = User::factory()->create();
        $user->assignRole('TRAINER');

        return $user;
    }

    public function test_store_creates_a_pending_document_and_dispatches_analysis(): void
    {
        Queue::fake();
        $instructor = $this->instructor();

        $response = $this->actingAs($instructor)->post(route('integrity.documents.store'), [
            'title' => 'Midterm Essay',
            'text' => str_repeat('word ', 300),
        ]);

        $document = IntegrityDocument::firstWhere('title', 'Midterm Essay');

        $response->assertRedirect(route('integrity.documents.show', $document));
        $this->assertSame($instructor->id, $document->instructor_id);
        $this->assertSame(IntegrityDocumentStatus::PENDING, $document->status);
        $this->assertSame('paste', $document->source->value);
        $this->assertSame(300, $document->word_count);

        Queue::assertPushed(RunIntegrityAnalysis::class);
    }

    public function test_store_without_text_or_file_fails_validation_gracefully(): void
    {
        $instructor = $this->instructor();

        $response = $this->actingAs($instructor)->post(route('integrity.documents.store'), ['title' => 'Empty']);

        $response->assertSessionHasErrors('text');
        $this->assertDatabaseMissing('integrity_documents', ['title' => 'Empty']);
    }

    public function test_reanalyze_dispatches_a_new_analysis_job(): void
    {
        Queue::fake();
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)->post(route('integrity.documents.reanalyze', $document))->assertRedirect();

        Queue::assertPushed(RunIntegrityAnalysis::class, 1);
    }

    public function test_writing_tool_run_dispatches_the_correct_type(): void
    {
        Queue::fake();
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->post(route('integrity.documents.tools.run', [$document, 'grammar']))
            ->assertRedirect();

        Queue::assertPushed(RunWritingTool::class);
    }

    public function test_writing_tool_run_rejects_an_unknown_type(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->post(route('integrity.documents.tools.run', [$document, 'not-a-real-tool']))
            ->assertNotFound();
    }

    public function test_review_update_transitions_status_and_writes_an_audit_row(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);
        $report = IntegrityReport::factory()->flagged()->for($document, 'document')->create();

        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'start', 'notes' => 'Looking into it.'])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(IntegrityReviewStatus::UNDER_REVIEW, $report->review_status);
        $this->assertSame($instructor->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);

        $this->assertDatabaseHas('integrity_audit_log', [
            'report_id' => $report->id,
            'user_id' => $instructor->id,
            'action' => 'under_review',
        ]);
        $this->assertSame(1, IntegrityAuditLog::where('report_id', $report->id)->count());
    }

    public function test_review_update_rejects_an_invalid_state_transition(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);
        $report = IntegrityReport::factory()->for($document, 'document')->create(['review_status' => IntegrityReviewStatus::NONE]);

        // none -> upheld directly is not allowed; must pass through under_review first.
        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'uphold'])
            ->assertStatus(422);

        $this->assertSame(IntegrityReviewStatus::NONE, $report->fresh()->review_status);
    }
}
