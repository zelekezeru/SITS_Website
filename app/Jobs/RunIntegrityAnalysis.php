<?php

namespace App\Jobs;

use App\Enums\IntegrityConfidence;
use App\Enums\IntegrityDocumentStatus;
use App\Enums\IntegrityReviewStatus;
use App\Enums\IntegrityVerdict;
use App\Events\IntegrityReportCompleted;
use App\Models\IntegrityAuditLog;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Notifications\IntegrityAnalysisFailed;
use App\Services\Integrity\Detection\ClaudeDetectionDriver;
use App\Services\Integrity\Detection\CompositeScorer;
use App\Services\Integrity\Detection\StatisticalAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates a document's AI-detection pass: statistical → Claude →
 * composite → persist IntegrityReport → fire IntegrityReportCompleted.
 *
 * Text extraction is NOT re-run here — extracted_text is populated at
 * upload time (DocumentController, Phase 6) and this repo's schema doesn't
 * retain the original file, so there's nothing to re-extract from. A blank
 * extracted_text is treated as a hard failure.
 */
class RunIntegrityAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const ENGINE_VERSION = '1.0.0';

    public $tries = 3;

    public $timeout = 300;

    public $backoff = [30, 90, 180];

    public function __construct(protected IntegrityDocument $document)
    {
        $this->onQueue('integrity');
    }

    public function handle(StatisticalAnalyzer $statisticalAnalyzer, ClaudeDetectionDriver $claudeDriver, CompositeScorer $scorer): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }

        $document->update(['status' => IntegrityDocumentStatus::PROCESSING]);

        $text = (string) $document->extracted_text;
        $wordCount = $document->word_count;

        if (trim($text) === '') {
            $this->markFailed($document, 'no_extracted_text');

            return;
        }

        if ($wordCount < config('integrity.min_words', 150)) {
            $this->persistInsufficientTextReport($document);

            return;
        }

        $statistical = $statisticalAnalyzer->analyze($text);
        $claudeResult = $this->runClaudePass($claudeDriver, $text, $wordCount);
        $composite = $scorer->score($statistical, $claudeResult, $text, $wordCount);

        $report = IntegrityReport::updateOrCreate(
            ['integrity_document_id' => $document->id],
            [
                'ai_probability' => $composite['ai_probability'],
                'confidence' => $composite['confidence'],
                'verdict_label' => $composite['verdict_label'],
                'statistical_signals' => $statistical['signals'],
                'claude_analysis' => $claudeResult,
                'sentence_scores' => $composite['sentence_scores'],
                'flagged' => $composite['flagged'],
                'review_status' => IntegrityReviewStatus::NONE,
                'engine_version' => self::ENGINE_VERSION,
                'analyzed_at' => now(),
            ]
        );

        $document->update(['status' => IntegrityDocumentStatus::COMPLETE]);

        $this->logAnalyzed($document, $report);

        event(new IntegrityReportCompleted($document, $report));
    }

    /**
     * Claude is the only piece of this pipeline that can genuinely fail
     * transiently (rate limit, network blip). If it's simply not
     * configured, that's permanent — degrade gracefully to statistical-only
     * scoring (CompositeScorer already supports this). If it IS configured
     * but the call itself errors, throw so Laravel's queue retries the
     * whole job per $tries, rather than silently shipping a half-scored
     * report.
     */
    protected function runClaudePass(ClaudeDetectionDriver $driver, string $text, int $wordCount): array
    {
        if (! $driver->isAvailable()) {
            return ['success' => false, 'error' => 'Claude provider not configured.'];
        }

        $chunkTrigger = config('integrity.chunk_trigger_words', 6000);
        $result = $wordCount <= $chunkTrigger
            ? $driver->analyze($text)
            : $this->analyzeInChunks($driver, $text);

        if (! $result['success']) {
            throw new \RuntimeException('Claude detection pass failed: '.($result['error'] ?? 'unknown error'));
        }

        return $result;
    }

    protected function analyzeInChunks(ClaudeDetectionDriver $driver, string $text): array
    {
        $chunks = $this->chunkText(
            $text,
            (int) config('integrity.chunk_words', 4000),
            (int) config('integrity.chunk_overlap_words', 200)
        );

        $results = [];
        $failedChunks = 0;
        foreach ($chunks as $chunk) {
            $chunkResult = $driver->analyze($chunk['text']);
            if ($chunkResult['success']) {
                $results[] = ['result' => $chunkResult, 'word_count' => $chunk['word_count']];
            } else {
                $failedChunks++;
            }
        }

        if ($failedChunks > 0) {
            // Not fatal as long as at least one chunk succeeded — but a partial
            // failure quietly narrows how much of the document the score is
            // actually based on, which is exactly the kind of thing that
            // shouldn't fail silently for an advisory tool.
            Log::channel('ai')->warning('Some Claude chunk analyses failed during integrity detection', [
                'document_id' => $this->document->id,
                'failed_chunks' => $failedChunks,
                'total_chunks' => count($chunks),
            ]);
        }

        if (empty($results)) {
            return ['success' => false, 'error' => 'All Claude chunk analyses failed.'];
        }

        return $this->aggregateChunkResults($results);
    }

    /**
     * @return list<array{text:string, word_count:int}>
     */
    protected function chunkText(string $text, int $chunkWords, int $overlapWords): array
    {
        $paragraphs = preg_split('/\n{2,}/u', trim($text)) ?: [$text];

        $chunks = [];
        $currentParagraphs = [];
        $currentWordCount = 0;

        foreach ($paragraphs as $paragraph) {
            $paragraphWordCount = $this->countWords($paragraph);

            if ($currentWordCount > 0 && $currentWordCount + $paragraphWordCount > $chunkWords) {
                $chunks[] = implode("\n\n", $currentParagraphs);

                $overlapText = $this->takeLastWords(implode("\n\n", $currentParagraphs), $overlapWords);
                $currentParagraphs = $overlapText !== '' ? [$overlapText] : [];
                $currentWordCount = $this->countWords($overlapText);
            }

            $currentParagraphs[] = $paragraph;
            $currentWordCount += $paragraphWordCount;
        }

        if (! empty($currentParagraphs)) {
            $chunks[] = implode("\n\n", $currentParagraphs);
        }

        return array_map(fn ($chunkText) => ['text' => $chunkText, 'word_count' => $this->countWords($chunkText)], $chunks);
    }

    protected function takeLastWords(string $text, int $n): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, -$n));
    }

    /**
     * str_word_count() is ASCII-only and returns ~0 for non-Latin script,
     * which would badly mis-size chunks (and risk an oversized single
     * "chunk" hitting Claude's context limit) for any non-English document.
     */
    protected function countWords(string $text): int
    {
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    /**
     * Word-count-weighted mean across chunks, plus the peak chunk. Sentence
     * flags are intentionally dropped for chunked documents — their indices
     * are chunk-local and can't be safely remapped back to whole-document
     * sentence indices across overlapping chunk boundaries.
     */
    protected function aggregateChunkResults(array $results): array
    {
        $totalWords = array_sum(array_column($results, 'word_count')) ?: 1;

        $weightedProbability = 0.0;
        $peak = 0;
        $styleObservations = [];
        $reasoningParts = [];
        $confidences = [];

        foreach ($results as $entry) {
            $probability = $entry['result']['probability'] ?? 50;
            $weight = $entry['word_count'] / $totalWords;

            $weightedProbability += $probability * $weight;
            $peak = max($peak, $probability);
            $styleObservations = [...$styleObservations, ...($entry['result']['style_observations'] ?? [])];
            $reasoningParts[] = $entry['result']['reasoning_summary'] ?? '';
            $confidences[] = $entry['result']['confidence'] ?? 'medium';
        }

        $averageProbability = (int) round($weightedProbability);

        return [
            'success' => true,
            'overall_assessment' => match (true) {
                $averageProbability >= 70 => 'likely_ai',
                $averageProbability <= 30 => 'likely_human',
                default => 'mixed',
            },
            'probability' => $averageProbability,
            'peak_chunk_probability' => $peak,
            'confidence' => match (true) {
                in_array('low', $confidences, true) => 'low',
                in_array('medium', $confidences, true) => 'medium',
                default => 'high',
            },
            'reasoning_summary' => implode(' ', array_filter($reasoningParts)),
            'sentence_flags' => [],
            'style_observations' => array_values(array_unique($styleObservations)),
        ];
    }

    protected function persistInsufficientTextReport(IntegrityDocument $document): void
    {
        $report = IntegrityReport::updateOrCreate(
            ['integrity_document_id' => $document->id],
            [
                'ai_probability' => null,
                'confidence' => IntegrityConfidence::LOW,
                'verdict_label' => IntegrityVerdict::INSUFFICIENT_TEXT,
                'statistical_signals' => [],
                'claude_analysis' => [],
                'sentence_scores' => [],
                'flagged' => false,
                'review_status' => IntegrityReviewStatus::NONE,
                'engine_version' => self::ENGINE_VERSION,
                'analyzed_at' => now(),
            ]
        );

        $document->update(['status' => IntegrityDocumentStatus::COMPLETE]);

        $this->logAnalyzed($document, $report);

        event(new IntegrityReportCompleted($document, $report));
    }

    protected function logAnalyzed(IntegrityDocument $document, IntegrityReport $report): void
    {
        if (! $document->instructor) {
            return;
        }

        IntegrityAuditLog::record($report, $document->instructor, 'analyze', [
            'verdict_label' => $report->verdict_label?->value,
            'ai_probability' => $report->ai_probability,
        ]);
    }

    protected function markFailed(IntegrityDocument $document, string $reason): void
    {
        $document->update([
            'status' => IntegrityDocumentStatus::FAILED,
            'failure_reason' => $reason,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $document = $this->document->fresh();
        if (! $document) {
            return;
        }

        $this->markFailed($document, $exception->getMessage());

        $document->instructor?->notify(new IntegrityAnalysisFailed($document, $exception->getMessage()));

        Log::channel('ai')->error('Integrity analysis job failed', [
            'document_id' => $document->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
