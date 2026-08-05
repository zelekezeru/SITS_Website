<?php

namespace Tests\Feature;

use App\Models\CorpusFingerprint;
use App\Models\Course;
use App\Models\IntegrityDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillIntegrityCorpusTest extends TestCase
{
    use RefreshDatabase;

    public function test_fingerprints_documents_missing_fingerprints_only(): void
    {
        $unfingerprinted = IntegrityDocument::factory()->create(['extracted_text' => 'Some historical submission text with enough words to shingle.']);
        $alreadyDone = IntegrityDocument::factory()->create(['extracted_text' => 'Already fingerprinted text with enough words to shingle properly.']);
        CorpusFingerprint::factory()->create(['integrity_document_id' => $alreadyDone->id]);

        $this->artisan('integrity:backfill-corpus')
            ->expectsOutputToContain('Fingerprinted 1 document(s).')
            ->assertExitCode(0);

        $this->assertGreaterThan(0, CorpusFingerprint::where('integrity_document_id', $unfingerprinted->id)->count());
    }

    public function test_scopes_to_a_course_when_the_option_is_given(): void
    {
        $course = Course::factory()->create();
        $inCourse = IntegrityDocument::factory()->create(['course_id' => $course->id, 'extracted_text' => 'In course document text with enough words to shingle.']);
        $outsideCourse = IntegrityDocument::factory()->create(['extracted_text' => 'Outside course document text with enough words to shingle.']);

        $this->artisan('integrity:backfill-corpus', ['--course' => $course->id])->assertExitCode(0);

        $this->assertGreaterThan(0, CorpusFingerprint::where('integrity_document_id', $inCourse->id)->count());
        $this->assertSame(0, CorpusFingerprint::where('integrity_document_id', $outsideCourse->id)->count());
    }
}
