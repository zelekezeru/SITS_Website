<?php

namespace Tests\Unit;

use App\Services\Integrity\Detection\CompositeScorer;
use App\Services\Integrity\Detection\StatisticalAnalyzer;
use Tests\TestCase;

class CompositeScorerTest extends TestCase
{
    private CompositeScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scorer = new CompositeScorer(new StatisticalAnalyzer);
    }

    private function statisticalFixture(int $sentenceCount = 3): array
    {
        $sentenceScores = [];
        for ($i = 0; $i < $sentenceCount; $i++) {
            $sentenceScores[] = [
                'index' => $i, 'text_hash' => md5((string) $i), 'start' => $i * 10, 'end' => $i * 10 + 9,
                'score' => 40, 'signals' => [],
            ];
        }

        return [
            'signals' => [
                'burstiness' => ['value' => 0.1, 'zscore_vs_baseline' => -2.0, 'direction' => 'ai_like'],
                'sentence_length_uniformity' => ['value' => 0.9, 'zscore_vs_baseline' => 2.0, 'direction' => 'ai_like'],
                'type_token_ratio' => ['value' => 0.3, 'zscore_vs_baseline' => -1.5, 'direction' => 'ai_like'],
                'ngram_repetition' => ['value' => 5, 'zscore_vs_baseline' => 1.0, 'direction' => 'ai_like'],
                'transition_density' => ['value' => 5, 'zscore_vs_baseline' => 2.0, 'direction' => 'ai_like'],
                'em_dash_rate' => ['value' => 3, 'zscore_vs_baseline' => 1.5, 'direction' => 'ai_like'],
                'paragraph_uniformity' => ['value' => 0.8, 'zscore_vs_baseline' => 1.0, 'direction' => 'ai_like'],
                'sentence_opener_diversity' => ['value' => 0.2, 'zscore_vs_baseline' => -1.0, 'direction' => 'ai_like'],
                'personal_voice_markers' => ['value' => 0, 'zscore_vs_baseline' => -1.0, 'direction' => 'ai_like'],
                'list_structure_density' => ['value' => 0, 'zscore_vs_baseline' => 0.0, 'direction' => 'neutral'],
                'readability_delta' => ['value' => 0, 'zscore_vs_baseline' => 0.0, 'direction' => 'neutral'],
            ],
            'sentence_scores' => $sentenceScores,
        ];
    }

    private function claudeFixture(array $overrides = []): array
    {
        return array_merge([
            'success' => true,
            'overall_assessment' => 'likely_ai',
            'probability' => 85,
            'confidence' => 'high',
            'reasoning_summary' => 'Uniform and generic throughout.',
            'sentence_flags' => [['index' => 1, 'reason' => 'generic phrasing']],
            'style_observations' => [],
        ], $overrides);
    }

    public function test_blends_statistical_and_claude_probabilities_using_configured_weights(): void
    {
        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(), str_repeat('word ', 400), 400);

        $expected = (int) round(0.45 * $result['statistical_probability'] + 0.55 * 85);
        $this->assertSame($expected, $result['ai_probability']);
    }

    public function test_high_probability_yields_likely_ai_verdict_and_flags(): void
    {
        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(['probability' => 90]), str_repeat('word ', 400), 400);

        $this->assertSame('likely_ai', $result['verdict_label']);
        $this->assertTrue($result['flagged']);
    }

    public function test_low_word_count_forces_low_confidence(): void
    {
        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(), 'short text', 50);

        $this->assertSame('low', $result['confidence']);
        $this->assertFalse($result['flagged'], 'low confidence must never be flagged, even at a high probability');
    }

    public function test_engine_disagreement_forces_low_confidence(): void
    {
        // Statistical fixture leans strongly AI-like; tell Claude the opposite.
        $result = $this->scorer->score(
            $this->statisticalFixture(),
            $this->claudeFixture(['probability' => 5, 'overall_assessment' => 'likely_human']),
            str_repeat('word ', 400),
            400
        );

        $this->assertSame('low', $result['confidence']);
    }

    public function test_heavy_quotation_density_forces_low_confidence(): void
    {
        $quoteHeavyText = str_repeat('"This whole sentence is quoted material from elsewhere." ', 30);

        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(), $quoteHeavyText, 400);

        $this->assertSame('low', $result['confidence']);
    }

    public function test_heavy_curly_smart_quotation_density_also_forces_low_confidence(): void
    {
        // Word/DOCX defaults to curly "smart quotes" — this is the common case
        // for real submissions, not an edge case, so it must be detected too.
        $quoteHeavyText = str_repeat("\u{201C}This whole sentence is quoted material from elsewhere.\u{201D} ", 30);

        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(), $quoteHeavyText, 400);

        $this->assertSame('low', $result['confidence']);
    }

    public function test_claude_unavailable_falls_back_to_statistical_only_with_low_confidence(): void
    {
        $result = $this->scorer->score(
            $this->statisticalFixture(),
            ['success' => false, 'error' => 'API down'],
            str_repeat('word ', 400),
            400
        );

        $this->assertSame('low', $result['confidence']);
        $this->assertNull($result['claude_probability']);
        $this->assertSame($result['statistical_probability'], $result['ai_probability']);
    }

    public function test_claude_flagged_sentences_get_a_score_bonus_and_marker(): void
    {
        $result = $this->scorer->score($this->statisticalFixture(), $this->claudeFixture(), str_repeat('word ', 400), 400);

        $this->assertSame(65, $result['sentence_scores'][1]['score']); // 40 + 25 bonus
        $this->assertContains('claude_flagged', $result['sentence_scores'][1]['signals']);
        $this->assertSame(40, $result['sentence_scores'][0]['score']); // untouched
    }

    public function test_low_probability_yields_likely_human_verdict(): void
    {
        $statistical = $this->statisticalFixture();
        foreach ($statistical['signals'] as $key => $signal) {
            $statistical['signals'][$key]['zscore_vs_baseline'] = -2.0;
        }

        $result = $this->scorer->score(
            $statistical,
            $this->claudeFixture(['probability' => 5, 'overall_assessment' => 'likely_human', 'confidence' => 'high']),
            str_repeat('word ', 400),
            400
        );

        $this->assertSame('likely_human', $result['verdict_label']);
        $this->assertFalse($result['flagged']);
    }
}
