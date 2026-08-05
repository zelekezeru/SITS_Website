<?php

namespace App\Services\Integrity\Detection;

use App\Enums\IntegrityConfidence;
use App\Enums\IntegrityVerdict;

/**
 * Merges the statistical pass and the Claude pass into one composite
 * report: ai_probability, confidence, verdict_label, and per-sentence
 * scores (statistical score adjusted by Claude's sentence_flags).
 *
 * Word-count/verdict gating (insufficient_text) happens upstream in the
 * orchestration job, before this scorer is even invoked — this class only
 * scores text that's already been judged worth analyzing.
 */
class CompositeScorer
{
    protected const DISAGREEMENT_THRESHOLD = 40;

    protected const LOW_WORD_COUNT = 300;

    protected const HEAVY_QUOTATION_RATIO = 0.3;

    protected const SENTENCE_FLAG_BONUS = 25;

    public function __construct(protected StatisticalAnalyzer $statisticalAnalyzer = new StatisticalAnalyzer) {}

    /**
     * @param  array{signals: array, sentence_scores: array}  $statistical
     * @param  array  $claude  Result of ClaudeDetectionDriver::analyze()
     * @return array{ai_probability:int, confidence:string, verdict_label:string, sentence_scores:array, statistical_probability:int, claude_probability:?int, flagged:bool}
     */
    public function score(array $statistical, array $claude, string $text, int $wordCount): array
    {
        $statisticalProbability = $this->statisticalAnalyzer->combinedProbability($statistical['signals']);
        $claudeAvailable = ! empty($claude['success']);
        $claudeProbability = $claudeAvailable ? (int) ($claude['probability'] ?? 50) : null;

        $weights = config('integrity.weights', ['statistical' => 0.45, 'claude' => 0.55]);

        if ($claudeAvailable) {
            $aiProbability = (int) round(
                $weights['statistical'] * $statisticalProbability
                + $weights['claude'] * $claudeProbability
            );
        } else {
            $aiProbability = $statisticalProbability;
        }
        $aiProbability = max(0, min(100, $aiProbability));

        $confidence = $this->resolveConfidence($claude, $claudeAvailable, $statisticalProbability, $claudeProbability, $text, $wordCount);
        $verdict = $this->resolveVerdict($aiProbability);

        $sentenceScores = $this->mergeSentenceScores($statistical['sentence_scores'], $claude['sentence_flags'] ?? []);

        $flagged = $aiProbability >= config('integrity.flag_threshold', 70) && $confidence !== IntegrityConfidence::LOW->value;

        return [
            'ai_probability' => $aiProbability,
            'confidence' => $confidence,
            'verdict_label' => $verdict,
            'sentence_scores' => $sentenceScores,
            'statistical_probability' => $statisticalProbability,
            'claude_probability' => $claudeProbability,
            'flagged' => $flagged,
        ];
    }

    protected function resolveConfidence(array $claude, bool $claudeAvailable, int $statisticalProbability, ?int $claudeProbability, string $text, int $wordCount): string
    {
        if ($wordCount < self::LOW_WORD_COUNT) {
            return IntegrityConfidence::LOW->value;
        }

        if (! $claudeAvailable) {
            return IntegrityConfidence::LOW->value;
        }

        if (abs($statisticalProbability - $claudeProbability) > self::DISAGREEMENT_THRESHOLD) {
            return IntegrityConfidence::LOW->value;
        }

        if ($this->quotationRatio($text) > self::HEAVY_QUOTATION_RATIO) {
            return IntegrityConfidence::LOW->value;
        }

        $claudeConfidence = $claude['confidence'] ?? 'medium';

        return IntegrityConfidence::tryFrom($claudeConfidence)?->value ?? IntegrityConfidence::MEDIUM->value;
    }

    protected function resolveVerdict(int $aiProbability): string
    {
        return match (true) {
            $aiProbability >= 70 => IntegrityVerdict::LIKELY_AI->value,
            $aiProbability <= 30 => IntegrityVerdict::LIKELY_HUMAN->value,
            default => IntegrityVerdict::MIXED->value,
        };
    }

    protected function quotationRatio(string $text): float
    {
        $length = mb_strlen($text);
        if ($length === 0) {
            return 0.0;
        }

        // Straight quotes AND curly "smart quotes" — Word/DOCX defaults to the
        // latter, so matching only straight quotes would silently miss most
        // quote-heavy submissions and defeat this confidence safeguard for them.
        $openCurly = "\u{201C}";
        $closeCurly = "\u{201D}";
        preg_match_all('/"[^"]*"|'.$openCurly.'[^'.$closeCurly.']*'.$closeCurly.'/u', $text, $matches);
        $quoted = array_sum(array_map('mb_strlen', $matches[0] ?? []));

        return $quoted / $length;
    }

    /**
     * @param  list<array{index:int, text_hash:string, start:int, end:int, score:int, signals:list<string>}>  $sentenceScores
     * @param  list<array{index:int, reason:string}>  $claudeFlags
     * @return list<array{index:int, text_hash:string, start:int, end:int, score:int, signals:list<string>}>
     */
    protected function mergeSentenceScores(array $sentenceScores, array $claudeFlags): array
    {
        $flagsByIndex = [];
        foreach ($claudeFlags as $flag) {
            $flagsByIndex[$flag['index'] ?? -1][] = $flag['reason'] ?? 'claude_flagged';
        }

        return array_map(function ($sentence) use ($flagsByIndex) {
            if (isset($flagsByIndex[$sentence['index']])) {
                $sentence['score'] = max(0, min(100, $sentence['score'] + self::SENTENCE_FLAG_BONUS));
                $sentence['signals'] = [...$sentence['signals'], 'claude_flagged'];
            }

            return $sentence;
        }, $sentenceScores);
    }
}
