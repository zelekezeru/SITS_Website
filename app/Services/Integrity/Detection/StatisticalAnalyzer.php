<?php

namespace App\Services\Integrity\Detection;

use App\Models\Setting;

/**
 * Pure-PHP statistical signal analysis — no API cost, always runs on the
 * full document text regardless of length (chunking is a Claude-specific
 * concern, see ClaudeDetectionDriver / RunIntegrityAnalysis).
 *
 * Each of the 11 signals is reported as {value, zscore_vs_baseline,
 * direction}. `direction` is 'ai_like' | 'human_like' | 'neutral', derived
 * from a per-signal sign (which way a high value points) and a small
 * dead-zone around zscore 0 so weak evidence doesn't get over-claimed.
 */
class StatisticalAnalyzer
{
    /**
     * Signals whose zscore feeds the combined AI-lean score, and the sign of
     * that contribution (+1 = high value leans AI-like, -1 = high value
     * leans human-like). `readability_delta` is intentionally excluded —
     * it's reported for context but isn't a directional AI/human signal.
     */
    protected const SIGNAL_DIRECTIONS = [
        'burstiness' => -1,
        'sentence_length_uniformity' => 1,
        'type_token_ratio' => -1,
        'ngram_repetition' => 1,
        'transition_density' => 1,
        'em_dash_rate' => 1,
        'paragraph_uniformity' => 1,
        'sentence_opener_diversity' => -1,
        'personal_voice_markers' => -1,
        'list_structure_density' => 1,
    ];

    protected const NEUTRAL_ZSCORE_BAND = 0.5;

    protected const PERSONAL_VOICE_MARKERS = [
        'i think', 'i believe', 'in my experience', 'in my opinion', 'i feel',
        ' i ', " i'm ", " i've ", ' my ', ' me ', ' myself ',
    ];

    /**
     * @return array{signals: array<string, array{value: float, zscore_vs_baseline: float, direction: string}>, sentence_scores: list<array{index:int, text_hash:string, start:int, end:int, score:int, signals: list<string>}>}
     */
    public function analyze(string $text): array
    {
        $sentences = $this->splitSentences($text);
        $paragraphs = $this->splitParagraphs($text);
        $words = $this->splitWords($text);
        $wordCount = count($words);

        $signals = [
            'burstiness' => $this->burstiness($sentences),
            'sentence_length_uniformity' => $this->sentenceLengthUniformity($sentences),
            'type_token_ratio' => $this->movingAverageTtr($words),
            'ngram_repetition' => $this->ngramRepetition($words),
            'transition_density' => $this->transitionDensity($text, $wordCount),
            'em_dash_rate' => $this->emDashRate($text, $wordCount),
            'paragraph_uniformity' => $this->paragraphUniformity($paragraphs),
            'sentence_opener_diversity' => $this->sentenceOpenerDiversity($sentences),
            'personal_voice_markers' => $this->personalVoiceMarkers($text, $wordCount),
            'list_structure_density' => $this->listStructureDensity($text, $wordCount),
            'readability_delta' => $this->readabilityDelta($words, $sentences),
        ];

        $scored = [];
        foreach ($signals as $key => $value) {
            $scored[$key] = $this->scoreSignal($key, $value);
        }

        return [
            'signals' => $scored,
            'sentence_scores' => $this->scoreSentences($sentences),
        ];
    }

    /**
     * Combine every directional signal's zscore (sign-adjusted) into one
     * 0-100 AI-lean probability via a logistic squash centered on 50.
     */
    public function combinedProbability(array $signals): int
    {
        $weighted = [];
        foreach (self::SIGNAL_DIRECTIONS as $key => $sign) {
            if (isset($signals[$key]['zscore_vs_baseline'])) {
                $weighted[] = $sign * $signals[$key]['zscore_vs_baseline'];
            }
        }

        if (empty($weighted)) {
            return 50;
        }

        $meanZ = array_sum($weighted) / count($weighted);
        $probability = 100 / (1 + exp(-$meanZ));

        return (int) round(max(0, min(100, $probability)));
    }

    protected function scoreSignal(string $key, float $value): array
    {
        $baseline = $this->baselineFor($key);
        $stddev = $baseline['stddev'] > 0 ? $baseline['stddev'] : 1;
        $zscore = ($value - $baseline['mean']) / $stddev;

        $sign = self::SIGNAL_DIRECTIONS[$key] ?? 0;
        $direction = 'neutral';
        if ($sign !== 0 && abs($zscore) >= self::NEUTRAL_ZSCORE_BAND) {
            $direction = ($zscore * $sign) > 0 ? 'ai_like' : 'human_like';
        }

        return [
            'value' => round($value, 4),
            'zscore_vs_baseline' => round($zscore, 4),
            'direction' => $direction,
        ];
    }

    /**
     * Baselines default from config/integrity.php but are overridable per
     * signal via Setting (same override pattern as the rest of the AI
     * config in this codebase) — `integrity:recalibrate` writes here.
     *
     * @return array{mean: float, stddev: float}
     */
    protected function baselineFor(string $key): array
    {
        $default = config("integrity.baselines.{$key}", ['mean' => 0, 'stddev' => 1]);

        return [
            'mean' => (float) Setting::get("integrity_baseline_{$key}_mean", $default['mean']),
            'stddev' => (float) Setting::get("integrity_baseline_{$key}_stddev", $default['stddev']),
        ];
    }

    // =========================================================================
    // Tokenization
    // =========================================================================

    /**
     * @return list<array{text:string, start:int, end:int}>
     */
    protected function splitSentences(string $text): array
    {
        $sentences = [];
        $length = mb_strlen($text);

        preg_match_all('/[^.!?]+[.!?]+(\s+|$)|[^.!?]+$/u', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] ?? [] as [$match, $byteOffset]) {
            $trimmed = trim($match);
            if ($trimmed === '') {
                continue;
            }

            $start = mb_strlen(substr($text, 0, $byteOffset));
            $sentences[] = [
                'text' => $trimmed,
                'start' => $start,
                'end' => min($start + mb_strlen($trimmed), $length),
            ];
        }

        return $sentences;
    }

    /**
     * @return list<string>
     */
    protected function splitParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\n{2,}/u', trim($text)) ?: [];

        return array_values(array_filter($paragraphs, fn ($p) => trim($p) !== ''));
    }

    /**
     * @return list<string>
     */
    protected function splitWords(string $text): array
    {
        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    // =========================================================================
    // Signal computations
    // =========================================================================

    protected function sentenceLengths(array $sentences): array
    {
        return array_map(fn ($s) => count($this->splitWords($s['text'])), $sentences);
    }

    protected function burstiness(array $sentences): float
    {
        $lengths = $this->sentenceLengths($sentences);
        if (count($lengths) < 2) {
            return 0.0;
        }

        $mean = array_sum($lengths) / count($lengths);
        if ($mean == 0.0) {
            return 0.0;
        }

        return $this->stddev($lengths, $mean) / $mean;
    }

    protected function sentenceLengthUniformity(array $sentences): float
    {
        $lengths = $this->sentenceLengths($sentences);
        if (empty($lengths)) {
            return 0.0;
        }

        $mean = array_sum($lengths) / count($lengths);
        if ($mean == 0.0) {
            return 0.0;
        }

        $within = array_filter($lengths, fn ($l) => abs($l - $mean) <= $mean * 0.2);

        return count($within) / count($lengths);
    }

    protected function movingAverageTtr(array $words, int $window = 50): float
    {
        $count = count($words);
        if ($count === 0) {
            return 0.0;
        }

        if ($count <= $window) {
            $unique = count(array_unique(array_map('mb_strtolower', $words)));

            return $unique / $count;
        }

        $ratios = [];
        for ($i = 0; $i <= $count - $window; $i += $window) {
            $slice = array_slice($words, $i, $window);
            $unique = count(array_unique(array_map('mb_strtolower', $slice)));
            $ratios[] = $unique / count($slice);
        }

        return array_sum($ratios) / count($ratios);
    }

    protected function ngramRepetition(array $words): float
    {
        $count = count($words);
        if ($count < 4) {
            return 0.0;
        }

        $normalized = array_map(fn ($w) => mb_strtolower($w), $words);
        $repeated = 0;

        foreach ([3, 4] as $n) {
            $grams = [];
            for ($i = 0; $i <= $count - $n; $i++) {
                $gram = implode(' ', array_slice($normalized, $i, $n));
                $grams[$gram] = ($grams[$gram] ?? 0) + 1;
            }
            $repeated += array_sum(array_filter($grams, fn ($c) => $c > 1));
        }

        return $repeated / ($count / 1000);
    }

    protected function transitionDensity(string $text, int $wordCount): float
    {
        if ($wordCount === 0) {
            return 0.0;
        }

        $lower = mb_strtolower($text);
        $hits = 0;
        foreach (config('integrity.transition_stoplist', []) as $phrase) {
            $hits += substr_count($lower, mb_strtolower($phrase));
        }

        return $hits / ($wordCount / 1000);
    }

    protected function emDashRate(string $text, int $wordCount): float
    {
        if ($wordCount === 0) {
            return 0.0;
        }

        $count = substr_count($text, '—') + substr_count($text, '--');

        return $count / ($wordCount / 1000);
    }

    protected function paragraphUniformity(array $paragraphs): float
    {
        if (count($paragraphs) < 2) {
            return 0.0;
        }

        $counts = array_map(fn ($p) => count($this->splitWords($p)), $paragraphs);
        $mean = array_sum($counts) / count($counts);
        if ($mean == 0.0) {
            return 0.0;
        }

        return 1 - min(1, $this->stddev($counts, $mean) / $mean);
    }

    protected function sentenceOpenerDiversity(array $sentences): float
    {
        if (empty($sentences)) {
            return 0.0;
        }

        $openers = array_map(function ($s) {
            $words = $this->splitWords($s['text']);
            $opener = mb_strtolower(implode(' ', array_slice($words, 0, 2)));

            return $opener;
        }, $sentences);

        return count(array_unique($openers)) / count($openers);
    }

    protected function personalVoiceMarkers(string $text, int $wordCount): float
    {
        if ($wordCount === 0) {
            return 0.0;
        }

        // Padded so a document/sentence that STARTS with "I ..." still hits the
        // space-anchored markers (' i ', " i'm ", ...) the same as a mid-text
        // occurrence would — otherwise the single most common personal-voice
        // opener ("I think...", "I'm not sure...") is undercounted whenever it
        // happens to lead the text, which is common.
        $padded = ' '.mb_strtolower($text).' ';

        $hits = 0;
        foreach (self::PERSONAL_VOICE_MARKERS as $marker) {
            $hits += substr_count($padded, $marker);
        }

        return $hits / ($wordCount / 1000);
    }

    /**
     * True if the (already-lowercased) text contains any personal-voice
     * marker — same marker list and padding as personalVoiceMarkers(), used
     * by per-sentence scoring so a sentence's personal-voice flag agrees
     * with what the document-level signal would count.
     */
    protected function hasPersonalVoiceMarker(string $lowerText): bool
    {
        $padded = ' '.$lowerText.' ';

        foreach (self::PERSONAL_VOICE_MARKERS as $marker) {
            if (str_contains($padded, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function listStructureDensity(string $text, int $wordCount): float
    {
        if ($wordCount === 0) {
            return 0.0;
        }

        $lines = preg_split('/\n/u', $text) ?: [];
        $hits = 0;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (preg_match('/^(\d+[\.\)]|[-*•])\s+/u', $trimmed)) {
                $hits++;
            } elseif (mb_strlen($trimmed) <= 60 && ! preg_match('/[.!?\x{1362}\x{1367}\x{1368}]$/u', $trimmed) && count($this->splitWords($trimmed)) <= 6) {
                // Sentence-ending punctuation check includes Ethiopic full stop
                // (። U+1362) and question/exclamation marks (፧ ፨) — SITS is an
                // Ethiopian institution and this signal must not mistake a
                // short, complete Amharic sentence for a heading just because
                // it doesn't end in a Latin period.
                $hits++; // heuristic heading
            }
        }

        return $hits / ($wordCount / 1000);
    }

    protected function readabilityDelta(array $words, array $sentences): float
    {
        $wordCount = count($words);
        $sentenceCount = max(1, count($sentences));

        if ($wordCount === 0) {
            return 0.0;
        }

        $syllables = array_sum(array_map([$this, 'countSyllables'], $words));

        $flesch = 206.835
            - 1.015 * ($wordCount / $sentenceCount)
            - 84.6 * ($syllables / $wordCount);

        $baselineMean = config('integrity.baselines.readability_delta.mean', 0.0);

        return $flesch - $baselineMean;
    }

    protected function countSyllables(string $word): int
    {
        $word = mb_strtolower(preg_replace('/[^a-z]/i', '', $word) ?? '');
        if ($word === '') {
            return 1;
        }

        preg_match_all('/[aeiouy]+/u', $word, $matches);
        $count = count($matches[0]);

        if (str_ends_with($word, 'e') && $count > 1) {
            $count--;
        }

        return max(1, $count);
    }

    // =========================================================================
    // Sentence-level scoring
    // =========================================================================

    /**
     * @return list<array{index:int, text_hash:string, start:int, end:int, score:int, signals: list<string>}>
     */
    protected function scoreSentences(array $sentences): array
    {
        if (empty($sentences)) {
            return [];
        }

        $lengths = $this->sentenceLengths($sentences);
        $meanLength = array_sum($lengths) / count($lengths);

        $results = [];
        foreach ($sentences as $index => $sentence) {
            $flags = [];
            $score = 30; // baseline neutral-low score, nudged up by per-sentence red flags

            $length = $lengths[$index];
            if ($meanLength > 0 && abs($length - $meanLength) <= $meanLength * 0.15) {
                $score += 15;
                $flags[] = 'uniform_length';
            }

            $lowerSentence = mb_strtolower($sentence['text']);
            foreach (config('integrity.transition_stoplist', []) as $phrase) {
                if (str_contains($lowerSentence, mb_strtolower($phrase))) {
                    $score += 20;
                    $flags[] = 'generic_transition';
                    break;
                }
            }

            if (str_contains($sentence['text'], '—')) {
                $score += 10;
                $flags[] = 'em_dash';
            }

            if ($this->hasPersonalVoiceMarker($lowerSentence)) {
                $score -= 15;
                $flags[] = 'personal_voice';
            } else {
                $score += 5;
            }

            $score = max(0, min(100, $score));

            $results[] = [
                'index' => $index,
                'text_hash' => md5($sentence['text']),
                'start' => $sentence['start'],
                'end' => $sentence['end'],
                'score' => $score,
                'signals' => $flags,
            ];
        }

        return $results;
    }

    protected function stddev(array $values, ?float $mean = null): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean ??= array_sum($values) / $count;
        $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $count;

        return sqrt($variance);
    }
}
