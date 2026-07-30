<?php

namespace App\Services\Integrity;

use Smalot\PdfParser\Parser as PdfParser;

/**
 * Extracts normalized plain text from an uploaded document (docx/pdf/txt)
 * or pasted text, for the integrity detection pipeline to work on.
 *
 * Scope is deliberately narrow: extraction + normalization + word count,
 * plus the one extraction-time failure this repo can't recover from
 * (a scanned PDF with no text layer). Whether a document has *enough* text
 * to run AI detection on (min/max word thresholds) is a Phase 3 concern —
 * that's a verdict about analysis, not a fact about extraction.
 */
class TextExtractor
{
    /**
     * @return array{success:bool, text:?string, word_count:int, failure_reason:?string}
     */
    public function extractFromPath(string $path, string $extension): array
    {
        $extension = strtolower(ltrim($extension, '.'));

        return match ($extension) {
            'docx' => $this->extractDocx($path),
            'pdf' => $this->extractPdf($path),
            'txt' => $this->extractTxt($path),
            default => $this->failure('unsupported_file_type'),
        };
    }

    /**
     * @return array{success:bool, text:?string, word_count:int, failure_reason:?string}
     */
    public function extractFromPastedText(string $text): array
    {
        $normalized = $this->normalize($text);

        return [
            'success' => true,
            'text' => $normalized,
            'word_count' => $this->countWords($normalized),
            'failure_reason' => null,
        ];
    }

    protected function extractDocx(string $path): array
    {
        $zip = new \ZipArchive;

        if ($zip->open($path) !== true) {
            return $this->failure('corrupted_file');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return $this->failure('corrupted_file');
        }

        try {
            $previousSetting = libxml_use_internal_errors(true);
            $document = new \SimpleXMLElement($xml);
            libxml_use_internal_errors($previousSetting);
        } catch (\Throwable) {
            return $this->failure('corrupted_file');
        }

        $document->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = $document->xpath('//w:p') ?: [];

        $lines = [];
        foreach ($paragraphs as $paragraph) {
            $runs = $paragraph->xpath('.//w:t') ?: [];
            $line = implode('', array_map(fn ($t) => (string) $t, $runs));
            $lines[] = $line;
        }

        $text = $this->normalize(implode("\n\n", $lines));

        return [
            'success' => true,
            'text' => $text,
            'word_count' => $this->countWords($text),
            'failure_reason' => null,
        ];
    }

    protected function extractPdf(string $path): array
    {
        try {
            $document = (new PdfParser)->parseFile($path);
            $rawText = $document->getText();
            $pageCount = count($document->getPages());
        } catch (\Throwable) {
            return $this->failure('corrupted_file');
        }

        $text = $this->normalize($rawText);
        $wordCount = $this->countWords($text);

        if ($pageCount > 1 && $wordCount < 40) {
            return $this->failure('likely_scanned_pdf_no_ocr');
        }

        return [
            'success' => true,
            'text' => $text,
            'word_count' => $wordCount,
            'failure_reason' => null,
        ];
    }

    protected function extractTxt(string $path): array
    {
        $raw = @file_get_contents($path);

        if ($raw === false) {
            return $this->failure('corrupted_file');
        }

        $encoding = mb_detect_encoding($raw, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true) ?: 'UTF-8';
        $text = $encoding === 'UTF-8' ? $raw : mb_convert_encoding($raw, 'UTF-8', $encoding);
        $text = $this->normalize($text);

        return [
            'success' => true,
            'text' => $text,
            'word_count' => $this->countWords($text),
            'failure_reason' => null,
        ];
    }

    /**
     * Collapse repeated whitespace, strip control characters, and cap
     * paragraph breaks — while preserving sentence punctuation and the
     * paragraph structure itself.
     */
    public function normalize(string $text): string
    {
        // Strip control characters (category Cc) but keep newlines and tabs.
        $text = preg_replace('/[^\PC\n\t]/u', '', $text) ?? $text;

        $text = str_replace("\r\n", "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/ *\n */', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    public function countWords(string $text): int
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY));
    }

    protected function failure(string $reason): array
    {
        return [
            'success' => false,
            'text' => null,
            'word_count' => 0,
            'failure_reason' => $reason,
        ];
    }
}
