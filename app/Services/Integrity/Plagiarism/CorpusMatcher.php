<?php

namespace App\Services\Integrity\Plagiarism;

use App\Models\CorpusFingerprint;
use App\Models\IntegrityDocument;
use App\Models\PlagiarismReport;
use Illuminate\Support\Collection;

/**
 * Matches a document's shingle fingerprints against the rest of the
 * corpus. Similarity is coverage-based: the % of the target document's
 * unique shingles that also appear in a given other document (or, for
 * overall_similarity, in ANY other document).
 */
class CorpusMatcher
{
    protected const MATCH_THRESHOLD_PCT = 8;

    public function __construct(protected Fingerprinter $fingerprinter = new Fingerprinter) {}

    /**
     * @return array{overall_similarity:int, matches:array, corpus_size:int}
     */
    public function match(IntegrityDocument $document, bool $includeSelf = false): array
    {
        $targetRows = CorpusFingerprint::where('integrity_document_id', $document->id)->get(['shingle_hash', 'position']);
        $uniqueTargetHashes = $targetRows->pluck('shingle_hash')->unique();

        if ($uniqueTargetHashes->isEmpty()) {
            return ['overall_similarity' => 0, 'matches' => [], 'corpus_size' => 0];
        }

        $excludedDocumentIds = $this->excludedDocumentIds($document, $includeSelf);

        $corpusSize = IntegrityDocument::whereNotIn('id', $excludedDocumentIds)
            ->whereHas('fingerprints')
            ->count();

        $matchingRows = CorpusFingerprint::whereIn('shingle_hash', $uniqueTargetHashes)
            ->whereNotIn('integrity_document_id', $excludedDocumentIds)
            ->get(['integrity_document_id', 'shingle_hash']);

        if ($matchingRows->isEmpty()) {
            return ['overall_similarity' => 0, 'matches' => [], 'corpus_size' => $corpusSize];
        }

        $matches = [];
        $allMatchedHashes = collect();

        foreach ($matchingRows->groupBy('integrity_document_id') as $matchedDocumentId => $rows) {
            $matchedHashes = $rows->pluck('shingle_hash')->unique();
            $allMatchedHashes = $allMatchedHashes->merge($matchedHashes);

            $similarity = (int) round(($matchedHashes->count() / $uniqueTargetHashes->count()) * 100);
            if ($similarity < self::MATCH_THRESHOLD_PCT) {
                continue;
            }

            $matchedDocument = IntegrityDocument::find($matchedDocumentId);
            if (! $matchedDocument) {
                continue;
            }

            $matches[] = [
                'source_type' => 'corpus',
                'matched_document_id' => $matchedDocumentId,
                'matched_title' => $matchedDocument->title,
                'similarity_pct' => $similarity,
                'shared_passages' => $this->reconstructSharedPassages($document, $matchedDocument, $matchedHashes),
            ];
        }

        usort($matches, fn ($a, $b) => $b['similarity_pct'] <=> $a['similarity_pct']);

        $overallSimilarity = (int) round(($allMatchedHashes->unique()->count() / $uniqueTargetHashes->count()) * 100);

        return [
            'overall_similarity' => min(100, $overallSimilarity),
            'matches' => $matches,
            'corpus_size' => $corpusSize,
        ];
    }

    public function matchAndPersist(IntegrityDocument $document, bool $includeSelf = false): PlagiarismReport
    {
        $result = $this->match($document, $includeSelf);

        return PlagiarismReport::updateOrCreate(
            ['integrity_document_id' => $document->id],
            [
                'overall_similarity' => $result['overall_similarity'],
                'matches' => $result['matches'],
                'corpus_size' => $result['corpus_size'],
                'analyzed_at' => now(),
            ]
        );
    }

    /**
     * @return list<int>
     */
    protected function excludedDocumentIds(IntegrityDocument $document, bool $includeSelf): array
    {
        $ids = [$document->id];

        if (! $includeSelf && $document->student_id) {
            $ids = [
                ...$ids,
                ...IntegrityDocument::where('student_id', $document->student_id)
                    ->where('id', '!=', $document->id)
                    ->pluck('id')
                    ->all(),
            ];
        }

        return $ids;
    }

    /**
     * @return list<array{source_excerpt:string, matched_excerpt:?string, start:int, end:int}>
     */
    protected function reconstructSharedPassages(IntegrityDocument $target, IntegrityDocument $matched, Collection $sharedHashes): array
    {
        $targetRows = CorpusFingerprint::where('integrity_document_id', $target->id)
            ->whereIn('shingle_hash', $sharedHashes)
            ->orderBy('position')
            ->get(['shingle_hash', 'position']);

        $matchedFirstPositionByHash = CorpusFingerprint::where('integrity_document_id', $matched->id)
            ->whereIn('shingle_hash', $sharedHashes)
            ->get(['shingle_hash', 'position'])
            ->groupBy('shingle_hash')
            ->map(fn ($rows) => $rows->min('position'));

        $targetWords = $this->fingerprinter->words((string) $target->extracted_text);
        $matchedWords = $this->fingerprinter->words((string) $matched->extracted_text);

        $runs = $this->groupConsecutive($targetRows->pluck('position')->all());

        $passages = [];
        foreach ($runs as $run) {
            $startPos = min($run);
            $endWordPos = max($run) + (Fingerprinter::SHINGLE_SIZE - 1);

            $startHash = $targetRows->firstWhere('position', $startPos)?->shingle_hash;
            $matchedStartPos = $startHash !== null ? ($matchedFirstPositionByHash[$startHash] ?? null) : null;

            $spanLength = $endWordPos - $startPos + 1;

            $passages[] = [
                'source_excerpt' => implode(' ', array_slice($targetWords, $startPos, $spanLength)),
                'matched_excerpt' => $matchedStartPos !== null
                    ? implode(' ', array_slice($matchedWords, $matchedStartPos, $spanLength))
                    : null,
                'start' => $startPos,
                'end' => $endWordPos,
            ];
        }

        return $passages;
    }

    /**
     * @param  list<int>  $positions
     * @return list<list<int>>
     */
    protected function groupConsecutive(array $positions): array
    {
        sort($positions);

        $groups = [];
        $current = [];

        foreach ($positions as $position) {
            if (empty($current) || $position === end($current) + 1) {
                $current[] = $position;
            } else {
                $groups[] = $current;
                $current = [$position];
            }
        }

        if (! empty($current)) {
            $groups[] = $current;
        }

        return $groups;
    }
}
