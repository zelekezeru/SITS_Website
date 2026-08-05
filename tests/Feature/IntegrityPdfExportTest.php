<?php

namespace Tests\Feature;

use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\PlagiarismReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Smalot\PdfParser\Parser as PdfParser;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class IntegrityPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function instructor(): User
    {
        SpatieRole::findOrCreate('TRAINER');
        $user = User::factory()->create();
        $user->assignRole('TRAINER');

        return $user;
    }

    private function extractPdfText(string $bytes): string
    {
        return (new PdfParser)->parseContent($bytes)->getText();
    }

    public function test_export_returns_a_pdf_containing_the_disclaimer_and_all_core_sections(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Exported Essay',
            'extracted_text' => 'One sentence here. A second sentence follows. A third one closes it out.',
        ]);
        $report = IntegrityReport::factory()->flagged()->for($document, 'document')->create([
            'statistical_signals' => [
                'burstiness' => ['value' => 0.4, 'zscore_vs_baseline' => -1.2, 'direction' => 'ai_like'],
            ],
            'sentence_scores' => [
                ['index' => 0, 'text_hash' => 'a', 'start' => 0, 'end' => 18, 'score' => 80, 'signals' => []],
            ],
        ]);

        $response = $this->actingAs($instructor)->get(route('integrity.documents.export.pdf', $document));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('integrity-report-', $response->headers->get('content-disposition'));

        $text = $this->extractPdfText($response->getContent());

        $this->assertStringContainsString('Academic Integrity Report', $text);
        $this->assertStringContainsString('probabilistic indicators, not proof', $text);
        $this->assertStringContainsString('AI Detection Score', $text);
        $this->assertStringContainsString('Statistical Signals', $text);
        $this->assertStringContainsString('Sentence Heatmap', $text);
        $this->assertStringContainsString('Review Status', $text);
        $this->assertStringContainsString('Exported Essay', $text);
    }

    public function test_export_includes_plagiarism_section_when_a_report_exists(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);
        IntegrityReport::factory()->for($document, 'document')->create();
        PlagiarismReport::factory()->for($document, 'document')->create([
            'overall_similarity' => 42,
            'web_similarity' => 10,
            'matches' => [
                ['source_type' => 'corpus', 'matched_title' => 'Another Student Paper', 'similarity_pct' => 42],
            ],
        ]);

        $response = $this->actingAs($instructor)->get(route('integrity.documents.export.pdf', $document));
        $text = $this->extractPdfText($response->getContent());

        $this->assertStringContainsString('Plagiarism', $text);
        $this->assertStringContainsString('Another Student Paper', $text);
    }

    public function test_export_writes_an_audit_row(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);
        $report = IntegrityReport::factory()->for($document, 'document')->create();

        $this->actingAs($instructor)->get(route('integrity.documents.export.pdf', $document));

        $this->assertDatabaseHas('integrity_audit_log', [
            'report_id' => $report->id,
            'user_id' => $instructor->id,
            'action' => 'exported',
        ]);
    }

    public function test_export_without_a_report_yet_returns_a_graceful_error(): void
    {
        $instructor = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->get(route('integrity.documents.export.pdf', $document))
            ->assertStatus(422);
    }

    public function test_another_instructor_cannot_export_someone_elses_document(): void
    {
        $owner = $this->instructor();
        $intruder = $this->instructor();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $owner->id]);
        IntegrityReport::factory()->for($document, 'document')->create();

        $this->actingAs($intruder)
            ->get(route('integrity.documents.export.pdf', $document))
            ->assertForbidden();
    }
}
