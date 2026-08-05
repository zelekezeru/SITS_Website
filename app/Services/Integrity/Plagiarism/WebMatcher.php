<?php

namespace App\Services\Integrity\Plagiarism;

use App\Models\IntegrityDocument;
use App\Models\PlagiarismReport;
use Illuminate\Support\Facades\Cache;

/**
 * Checks a document's most distinctive passages against the public web via
 * ClaudeWebSearchClient. A separate, explicitly-triggered check from
 * CorpusMatcher — never automatic, never blended into overall_similarity
 * (see PlagiarismReport.web_similarity).
 */
class WebMatcher
{
    protected const MAX_PASSAGES = 12;

    protected const MIN_PASSAGE_WORDS = 8;

    protected const MAX_PASSAGE_WORDS = 15;

    /**
     * Book-chapter:verse citation pattern, e.g. "John 3:16", "1 Corinthians 13:4-7".
     * Anything containing this is excluded — otherwise every paper "matches"
     * Bible websites.
     */
    protected const SCRIPTURE_PATTERN = '/\b([1-3]\s?)?[A-Z][a-z]+\.?\s+\d{1,3}:\d{1,3}(-\d{1,3})?\b/u';

    public function __construct(protected ClaudeWebSearchClient $client = new ClaudeWebSearchClient) {}

    /**
     * @return array{web_similarity:int, matches:array, passages_checked:int}
     */
    public function check(IntegrityDocument $document): array
    {
        $passages = $this->selectDistinctivePassages((string) $document->extracted_text);

        $matches = [];
        foreach ($passages as $passage) {
            $result = $this->cachedCheck($passage);

            if (($result['success'] ?? false) && ($result['found'] ?? false)) {
                $matches[] = [
                    'source_type' => 'web',
                    'passage' => $passage,
                    'url' => $result['url'] ?? null,
                    'source_title' => $result['source_title'] ?? null,
                    'matched_excerpt' => $result['matched_excerpt'] ?? null,
                    'match_quality' => $result['match_quality'] ?? 'none',
                ];
            }
        }

        $webSimilarity = ! empty($passages)
            ? (int) round((count($matches) / count($passages)) * 100)
            : 0;

        return [
            'web_similarity' => $webSimilarity,
            'matches' => $matches,
            'passages_checked' => count($passages),
        ];
    }

    /**
     * Runs the web check and merges its matches into the document's
     * existing PlagiarismReport (creating one at 0% corpus similarity if
     * the corpus check hasn't run yet), preserving any prior corpus
     * matches rather than overwriting them.
     */
    public function checkAndPersist(IntegrityDocument $document): PlagiarismReport
    {
        $result = $this->check($document);

        $existing = $document->plagiarismReports()->latest('id')->first();
        $priorMatches = $existing
            ? array_values(array_filter($existing->matches ?? [], fn ($m) => ($m['source_type'] ?? null) !== 'web'))
            : [];

        return PlagiarismReport::updateOrCreate(
            ['integrity_document_id' => $document->id],
            [
                'overall_similarity' => $existing->overall_similarity ?? 0,
                'web_similarity' => $result['web_similarity'],
                'matches' => [...$priorMatches, ...$result['matches']],
                'corpus_size' => $existing->corpus_size ?? 0,
                'analyzed_at' => now(),
            ]
        );
    }

    protected function cachedCheck(string $passage): array
    {
        $key = 'integrity:web-check:'.md5(mb_strtolower(trim($passage)));
        $ttlDays = (int) config('integrity.web_check_cache_days', 30);

        return Cache::remember($key, now()->addDays($ttlDays), fn () => $this->client->checkPassage($passage));
    }

    protected const QUOTE_PAIRS = [
        '"' => '"',
        "\u{201C}" => "\u{201D}", // curly double quotes “ ”
        "'" => "'",
        "\u{2018}" => "\u{2019}", // curly single quotes ‘ ’
        "\u{00AB}" => "\u{00BB}", // guillemets « »
    ];

    /**
     * @return list<string>
     */
    protected function selectDistinctivePassages(string $text): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($text)) ?: [];

        $passages = [];
        $insideOpenQuote = false;

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if ($sentence === '') {
                continue;
            }

            if ($insideOpenQuote) {
                // Still inside a quotation that opened in an earlier sentence
                // and hasn't closed yet — a block quote split across sentence
                // boundaries by the sentence splitter. Skip until it closes.
                if ($this->endsQuote($sentence)) {
                    $insideOpenQuote = false;
                }

                continue;
            }

            if ($this->isFullyQuoted($sentence) || $this->isScripture($sentence)) {
                continue;
            }

            if ($this->opensUnclosedQuote($sentence)) {
                $insideOpenQuote = true;

                continue;
            }

            $words = preg_split('/\s+/u', $sentence, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $wordCount = count($words);

            if ($wordCount < self::MIN_PASSAGE_WORDS) {
                continue;
            }

            $span = $wordCount > self::MAX_PASSAGE_WORDS
                ? implode(' ', array_slice($words, 0, self::MAX_PASSAGE_WORDS))
                : implode(' ', $words);

            $passages[] = $span;

            if (count($passages) >= self::MAX_PASSAGES) {
                break;
            }
        }

        return $passages;
    }

    /** A sentence entirely wrapped in one quote pair, e.g. "This whole thing." */
    protected function isFullyQuoted(string $sentence): bool
    {
        $trimmed = trim($sentence, " \t\n\r\0\x0B.,;:");

        foreach (self::QUOTE_PAIRS as $open => $close) {
            if (str_starts_with($trimmed, $open) && str_ends_with($trimmed, $close)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A sentence that opens a quote whose closing mark never appears again
     * in the rest of the sentence. Checking for the closer ANYWHERE after
     * the opener (not just at the very end) matters because the naive
     * sentence splitter doesn't break after "punctuation immediately
     * followed by a closing quote mark" (e.g. `entirely."` doesn't split
     * before the next sentence) — so a fully self-contained quote followed
     * by more unrelated text often ends up merged into one "sentence" that
     * doesn't literally END with the closing mark, even though the quote
     * itself is already closed.
     */
    protected function opensUnclosedQuote(string $sentence): bool
    {
        foreach (self::QUOTE_PAIRS as $open => $close) {
            if (! str_starts_with($sentence, $open)) {
                continue;
            }

            $afterOpen = mb_substr($sentence, mb_strlen($open));

            return ! str_contains($afterOpen, $close);
        }

        return false;
    }

    /** A sentence that contains a closing quote mark, closing an earlier-opened quote. */
    protected function endsQuote(string $sentence): bool
    {
        foreach (self::QUOTE_PAIRS as $close) {
            if (str_contains($sentence, $close)) {
                return true;
            }
        }

        return false;
    }

    protected function isScripture(string $sentence): bool
    {
        return (bool) preg_match(self::SCRIPTURE_PATTERN, $sentence);
    }
}
