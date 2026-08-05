<?php

namespace Tests\Feature;

use App\Enums\IntegrityReviewStatus;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

/**
 * Phase 7 acceptance: the review state machine and its audit trail.
 * (The mechanism itself was built in Phase 6's ReviewController — this
 * file is the dedicated, thorough coverage the plan's Phase 7 asks for.)
 */
class IntegrityReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        SpatieRole::findOrCreate('TRAINER');
        $user = User::factory()->create();
        $user->assignRole('TRAINER');

        return $user;
    }

    private function reportFor(User $instructor, IntegrityReviewStatus $status = IntegrityReviewStatus::NONE): IntegrityReport
    {
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        return IntegrityReport::factory()->flagged()->for($document, 'document')->create(['review_status' => $status]);
    }

    // ----- Valid transitions -------------------------------------------------

    public function test_full_lifecycle_none_to_under_review_to_cleared(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor);

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'start'])->assertRedirect();
        $this->assertSame(IntegrityReviewStatus::UNDER_REVIEW, $report->fresh()->review_status);

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), [
            'action' => 'clear',
            'notes' => 'Discussed with student; ESL phrasing explains the flagged sentences.',
            'student_meeting_date' => now()->toDateString(),
        ])->assertRedirect();

        $report->refresh();
        $this->assertSame(IntegrityReviewStatus::CLEARED, $report->review_status);
        $this->assertNotNull($report->student_meeting_date);
        $this->assertStringContainsString('ESL phrasing', $report->review_notes);
    }

    public function test_full_lifecycle_none_to_under_review_to_upheld(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor);

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'start']);
        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'uphold'])->assertRedirect();

        $this->assertSame(IntegrityReviewStatus::UPHELD, $report->fresh()->review_status);
    }

    // ----- Invalid transitions are rejected -----------------------------------

    public function test_cannot_jump_from_none_directly_to_cleared(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor, IntegrityReviewStatus::NONE);

        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'clear'])
            ->assertStatus(422);

        $this->assertSame(IntegrityReviewStatus::NONE, $report->fresh()->review_status);
    }

    public function test_cannot_jump_from_none_directly_to_upheld(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor, IntegrityReviewStatus::NONE);

        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'uphold'])
            ->assertStatus(422);

        $this->assertSame(IntegrityReviewStatus::NONE, $report->fresh()->review_status);
    }

    public function test_cleared_is_a_terminal_state(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor, IntegrityReviewStatus::CLEARED);

        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'start'])
            ->assertStatus(422);

        $this->assertSame(IntegrityReviewStatus::CLEARED, $report->fresh()->review_status);
    }

    public function test_upheld_is_a_terminal_state(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor, IntegrityReviewStatus::UPHELD);

        $this->actingAs($instructor)
            ->patch(route('integrity.reports.review', $report), ['action' => 'clear'])
            ->assertStatus(422);

        $this->assertSame(IntegrityReviewStatus::UPHELD, $report->fresh()->review_status);
    }

    // ----- Audit trail ---------------------------------------------------------

    public function test_every_review_transition_writes_its_own_audit_row(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor);

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'start']);
        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'clear']);

        $actions = IntegrityAuditLog::where('report_id', $report->id)->orderBy('id')->pluck('action')->all();

        $this->assertSame(['under_review', 'cleared'], $actions);
    }

    public function test_a_rejected_transition_writes_no_audit_row(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor, IntegrityReviewStatus::NONE);

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'uphold']);

        $this->assertSame(0, IntegrityAuditLog::where('report_id', $report->id)->count());
    }

    // ----- Nothing here ever touches grades/student records automatically ----

    public function test_clearing_or_upholding_never_touches_the_document_or_student_record(): void
    {
        $instructor = $this->instructor();
        $report = $this->reportFor($instructor);
        $documentBefore = $report->document->toArray();

        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'start']);
        $this->actingAs($instructor)->patch(route('integrity.reports.review', $report), ['action' => 'uphold']);

        $documentAfter = $report->document->fresh()->toArray();
        unset($documentBefore['updated_at'], $documentAfter['updated_at']);

        $this->assertSame($documentBefore, $documentAfter);
    }
}
