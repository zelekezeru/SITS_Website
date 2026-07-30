<?php

namespace Tests\Unit;

use App\Services\Integrity\TextExtractor;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class TextExtractorTest extends TestCase
{
    private TextExtractor $extractor;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new TextExtractor;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_extracts_and_normalizes_pasted_text(): void
    {
        $result = $this->extractor->extractFromPastedText("Hello   world.\r\n\r\n\r\n\r\nThis is   a test.\t\n");

        $this->assertTrue($result['success']);
        $this->assertSame("Hello world.\n\nThis is a test.", $result['text']);
        $this->assertSame(6, $result['word_count']);
        $this->assertNull($result['failure_reason']);
    }

    public function test_extracts_text_from_a_valid_docx_file(): void
    {
        $path = $this->makeDocxFixture([
            'This is the first paragraph of a valid Word document.',
            'This is the second paragraph, with a distinct sentence.',
        ]);

        $result = $this->extractor->extractFromPath($path, 'docx');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('first paragraph', $result['text']);
        $this->assertStringContainsString('second paragraph', $result['text']);
        $this->assertGreaterThan(10, $result['word_count']);
        $this->assertNull($result['failure_reason']);
    }

    public function test_docx_extraction_fails_gracefully_on_a_corrupted_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_bad_').'.docx';
        $this->tempFiles[] = $path;
        file_put_contents($path, 'this is not a real zip/docx file');

        $result = $this->extractor->extractFromPath($path, 'docx');

        $this->assertFalse($result['success']);
        $this->assertSame('corrupted_file', $result['failure_reason']);
        $this->assertNull($result['text']);
    }

    public function test_extracts_text_from_a_valid_pdf_file(): void
    {
        $words = implode(' ', array_fill(0, 80, 'lorem'));
        $path = $this->makePdfFixture("<p>{$words}</p>");

        $result = $this->extractor->extractFromPath($path, 'pdf');

        $this->assertTrue($result['success']);
        $this->assertGreaterThanOrEqual(40, $result['word_count']);
        $this->assertNull($result['failure_reason']);
    }

    public function test_multi_page_pdf_with_almost_no_text_is_flagged_as_likely_scanned(): void
    {
        $html = '<div style="page-break-after: always;">hi there</div>'
            .'<div style="page-break-after: always;">still short</div>'
            .'<div>the end</div>';
        $path = $this->makePdfFixture($html);

        $result = $this->extractor->extractFromPath($path, 'pdf');

        $this->assertFalse($result['success']);
        $this->assertSame('likely_scanned_pdf_no_ocr', $result['failure_reason']);
    }

    public function test_pdf_extraction_fails_gracefully_on_a_corrupted_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_bad_').'.pdf';
        $this->tempFiles[] = $path;
        file_put_contents($path, '%PDF-1.4 this is not a real pdf body');

        $result = $this->extractor->extractFromPath($path, 'pdf');

        $this->assertFalse($result['success']);
        $this->assertSame('corrupted_file', $result['failure_reason']);
    }

    public function test_extracts_text_from_a_txt_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_txt_').'.txt';
        $this->tempFiles[] = $path;
        file_put_contents($path, "Plain text file.\r\n\r\nWith two paragraphs.");

        $result = $this->extractor->extractFromPath($path, 'txt');

        $this->assertTrue($result['success']);
        $this->assertSame("Plain text file.\n\nWith two paragraphs.", $result['text']);
        $this->assertSame(6, $result['word_count']);
    }

    public function test_unsupported_extension_fails_gracefully(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_bad_').'.exe';
        $this->tempFiles[] = $path;
        file_put_contents($path, 'irrelevant');

        $result = $this->extractor->extractFromPath($path, 'exe');

        $this->assertFalse($result['success']);
        $this->assertSame('unsupported_file_type', $result['failure_reason']);
    }

    /**
     * @param  list<string>  $paragraphs
     */
    private function makeDocxFixture(array $paragraphs): string
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_docx_').'.docx';
        $this->tempFiles[] = $path;

        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
<Default Extension="xml" ContentType="application/xml"/>
<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>
XML);

        $zip->addFromString('_rels/.rels', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML);

        $body = '';
        foreach ($paragraphs as $paragraph) {
            $escaped = htmlspecialchars($paragraph, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $body .= "<w:p><w:r><w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
        }

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            ."<w:body>{$body}</w:body></w:document>";

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();

        return $path;
    }

    private function makePdfFixture(string $bodyHtml): string
    {
        $path = tempnam(sys_get_temp_dir(), 'integrity_pdf_').'.pdf';
        $this->tempFiles[] = $path;

        $bytes = Pdf::loadHTML("<html><body>{$bodyHtml}</body></html>")->output();
        file_put_contents($path, $bytes);

        return $path;
    }
}
