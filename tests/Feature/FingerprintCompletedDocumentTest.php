<?php

namespace Tests\Feature;

use App\Events\IntegrityReportCompleted;
use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FingerprintCompletedDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatching_the_event_fingerprints_the_document_via_the_real_laravel_listener_wiring(): void
    {
        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'This document has enough distinct words to produce several shingles for testing purposes.',
        ]);
        $report = IntegrityReport::factory()->for($document, 'document')->create();

        $this->assertSame(0, CorpusFingerprint::where('integrity_document_id', $document->id)->count());

        event(new IntegrityReportCompleted($document, $report));

        $this->assertGreaterThan(0, CorpusFingerprint::where('integrity_document_id', $document->id)->count());
    }
}
