<?php

namespace Tests\Feature;

use App\Enums\IntegrityConfidence;
use App\Enums\IntegrityDocumentSource;
use App\Enums\IntegrityDocumentStatus;
use App\Enums\IntegrityReviewStatus;
use App\Enums\IntegrityVerdict;
use App\Enums\WritingReportType;
use App\Models\CorpusFingerprint;
use App\Models\Course;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\PlagiarismReport;
use App\Models\User;
use App\Models\WritingReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrityModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrity_document_generates_a_uuid_on_create_and_uses_it_as_route_key()
    {
        $document = IntegrityDocument::factory()->create();

        $this->assertNotEmpty($document->uuid);
        $this->assertSame('uuid', $document->getRouteKeyName());
    }

    public function test_integrity_document_casts_source_and_status_to_enums()
    {
        $document = IntegrityDocument::factory()->create([
            'source' => IntegrityDocumentSource::UPLOAD,
            'status' => IntegrityDocumentStatus::PROCESSING,
        ]);

        $this->assertInstanceOf(IntegrityDocumentSource::class, $document->source);
        $this->assertSame(IntegrityDocumentSource::UPLOAD, $document->source);
        $this->assertInstanceOf(IntegrityDocumentStatus::class, $document->status);
        $this->assertSame(IntegrityDocumentStatus::PROCESSING, $document->status);
    }

    public function test_integrity_document_is_soft_deleted()
    {
        $document = IntegrityDocument::factory()->create();

        $document->delete();

        $this->assertSoftDeleted($document);
    }

    public function test_integrity_document_belongs_to_instructor_student_and_course()
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $document = IntegrityDocument::factory()->create([
            'instructor_id' => $instructor->id,
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $this->assertTrue($document->instructor->is($instructor));
        $this->assertTrue($document->student->is($student));
        $this->assertTrue($document->course->is($course));
    }

    public function test_scope_for_instructor_filters_to_owned_documents_only()
    {
        $instructorA = User::factory()->create();
        $instructorB = User::factory()->create();

        IntegrityDocument::factory()->count(2)->create(['instructor_id' => $instructorA->id]);
        IntegrityDocument::factory()->create(['instructor_id' => $instructorB->id]);

        $results = IntegrityDocument::forInstructor($instructorA)->get();

        $this->assertCount(2, $results);
        $this->assertTrue($results->every(fn ($doc) => $doc->instructor_id === $instructorA->id));
    }

    public function test_integrity_report_belongs_to_document_and_casts_json_and_enum_columns()
    {
        $document = IntegrityDocument::factory()->create();

        $report = IntegrityReport::factory()->create([
            'integrity_document_id' => $document->id,
            'confidence' => IntegrityConfidence::HIGH,
            'verdict_label' => IntegrityVerdict::LIKELY_AI,
            'review_status' => IntegrityReviewStatus::NONE,
            'statistical_signals' => ['burstiness' => ['value' => 0.4]],
        ]);

        $this->assertTrue($report->document->is($document));
        $this->assertTrue($document->report->is($report));
        $this->assertInstanceOf(IntegrityConfidence::class, $report->confidence);
        $this->assertInstanceOf(IntegrityVerdict::class, $report->verdict_label);
        $this->assertInstanceOf(IntegrityReviewStatus::class, $report->review_status);
        $this->assertIsArray($report->statistical_signals);
        $this->assertSame(0.4, $report->statistical_signals['burstiness']['value']);
    }

    public function test_integrity_review_status_enforces_the_state_machine()
    {
        $none = IntegrityReviewStatus::NONE;
        $underReview = IntegrityReviewStatus::UNDER_REVIEW;

        $this->assertTrue($none->canTransitionTo($underReview));
        $this->assertFalse($none->canTransitionTo(IntegrityReviewStatus::UPHELD));
        $this->assertTrue($underReview->canTransitionTo(IntegrityReviewStatus::CLEARED));
        $this->assertTrue($underReview->canTransitionTo(IntegrityReviewStatus::UPHELD));
        $this->assertFalse(IntegrityReviewStatus::CLEARED->canTransitionTo($underReview));
    }

    public function test_plagiarism_report_belongs_to_document()
    {
        $document = IntegrityDocument::factory()->create();
        $report = PlagiarismReport::factory()->create(['integrity_document_id' => $document->id]);

        $this->assertTrue($report->document->is($document));
        $this->assertTrue($document->plagiarismReports->contains($report));
    }

    public function test_plagiarism_report_web_similarity_is_nullable_and_separate_from_corpus_similarity()
    {
        $document = IntegrityDocument::factory()->create();

        $noWebCheck = PlagiarismReport::factory()->create(['integrity_document_id' => $document->id]);
        $this->assertNull($noWebCheck->web_similarity);

        $withWebCheck = PlagiarismReport::factory()->create([
            'integrity_document_id' => $document->id,
            'overall_similarity' => 12,
            'web_similarity' => 65,
        ]);
        $this->assertSame(12, $withWebCheck->overall_similarity);
        $this->assertSame(65, $withWebCheck->web_similarity);
    }

    public function test_corpus_fingerprint_belongs_to_document()
    {
        $document = IntegrityDocument::factory()->create();
        $fingerprint = CorpusFingerprint::factory()->create(['integrity_document_id' => $document->id]);

        $this->assertTrue($fingerprint->document->is($document));
        $this->assertTrue($document->fingerprints->contains($fingerprint));
    }

    public function test_integrity_audit_log_records_an_action_against_a_report_and_user()
    {
        $document = IntegrityDocument::factory()->create();
        $report = IntegrityReport::factory()->create(['integrity_document_id' => $document->id]);
        $reviewer = User::factory()->create();

        $entry = IntegrityAuditLog::record($report, $reviewer, 'review_started', ['note' => 'flagged for ESL phrasing']);

        $this->assertTrue($entry->report->is($report));
        $this->assertTrue($entry->user->is($reviewer));
        $this->assertSame('review_started', $entry->action);
        $this->assertSame('flagged for ESL phrasing', $entry->meta['note']);
    }

    public function test_writing_report_belongs_to_document_and_casts_type_and_json()
    {
        $document = IntegrityDocument::factory()->create();
        $writing = WritingReport::factory()->create([
            'integrity_document_id' => $document->id,
            'type' => WritingReportType::GRAMMAR,
            'payload' => [['start' => 0, 'end' => 5, 'suggestion' => 'fix this']],
        ]);

        $this->assertTrue($writing->document->is($document));
        $this->assertTrue($document->writingReports->contains($writing));
        $this->assertInstanceOf(WritingReportType::class, $writing->type);
        $this->assertIsArray($writing->payload);
    }
}
