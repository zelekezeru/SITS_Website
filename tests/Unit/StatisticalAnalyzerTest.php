<?php

namespace Tests\Unit;

use App\Services\Integrity\Detection\StatisticalAnalyzer;
use Tests\TestCase;

class StatisticalAnalyzerTest extends TestCase
{
    private StatisticalAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new StatisticalAnalyzer;
    }

    public function test_analyze_returns_all_eleven_signals_with_expected_shape(): void
    {
        $result = $this->analyzer->analyze('This is a sentence. This is another sentence. And a third one here.');

        $this->assertArrayHasKey('signals', $result);
        $this->assertArrayHasKey('sentence_scores', $result);

        $expectedSignals = [
            'burstiness', 'sentence_length_uniformity', 'type_token_ratio', 'ngram_repetition',
            'transition_density', 'em_dash_rate', 'paragraph_uniformity', 'sentence_opener_diversity',
            'personal_voice_markers', 'list_structure_density', 'readability_delta',
        ];

        foreach ($expectedSignals as $key) {
            $this->assertArrayHasKey($key, $result['signals'], "Missing signal: {$key}");
            $this->assertArrayHasKey('value', $result['signals'][$key]);
            $this->assertArrayHasKey('zscore_vs_baseline', $result['signals'][$key]);
            $this->assertArrayHasKey('direction', $result['signals'][$key]);
        }
    }

    public function test_sentence_scores_array_length_equals_sentence_count(): void
    {
        $text = 'One sentence here. Two sentences now. Three is the count. Four sentences total.';
        $result = $this->analyzer->analyze($text);

        $this->assertCount(4, $result['sentence_scores']);
        foreach ($result['sentence_scores'] as $i => $sentence) {
            $this->assertSame($i, $sentence['index']);
            $this->assertArrayHasKey('text_hash', $sentence);
            $this->assertArrayHasKey('start', $sentence);
            $this->assertArrayHasKey('end', $sentence);
            $this->assertGreaterThanOrEqual(0, $sentence['score']);
            $this->assertLessThanOrEqual(100, $sentence['score']);
        }
    }

    public function test_uniform_robotic_text_leans_ai_like_on_combined_probability(): void
    {
        $sentences = [];
        for ($i = 0; $i < 20; $i++) {
            $sentences[] = 'Furthermore, this particular sentence contains exactly ten simple words.';
        }
        $text = implode(' ', $sentences);

        $result = $this->analyzer->analyze($text);
        $probability = $this->analyzer->combinedProbability($result['signals']);

        $this->assertGreaterThan(50, $probability);
    }

    public function test_varied_personal_text_leans_human_like_on_combined_probability(): void
    {
        $text = "I honestly wasn't sure what to expect when I started this project. "
            .'My professor kept pushing me to dig deeper, and — after a few false starts, a lot of coffee, '
            .'and one very late night — I think I finally found an angle that felt like mine. '
            .'Some parts still bug me, though; the third chapter especially! '
            .'In my experience, that kind of doubt never fully goes away, and honestly? Maybe it shouldn\'t.';

        $result = $this->analyzer->analyze($text);
        $probability = $this->analyzer->combinedProbability($result['signals']);

        $this->assertLessThan(50, $probability);
    }

    public function test_transition_density_counts_stoplist_phrases_per_thousand_words(): void
    {
        $text = 'Furthermore, this is important. Moreover, it plays a crucial role in everything.';
        $result = $this->analyzer->analyze($text);

        $this->assertGreaterThan(0, $result['signals']['transition_density']['value']);
    }

    public function test_em_dash_rate_detects_em_dashes(): void
    {
        $withDashes = 'This sentence—like many AI outputs—uses em-dashes constantly—everywhere—really.';
        $withoutDashes = 'This sentence does not use any special punctuation marks at all here.';

        $with = $this->analyzer->analyze($withDashes);
        $without = $this->analyzer->analyze($withoutDashes);

        $this->assertGreaterThan(
            $without['signals']['em_dash_rate']['value'],
            $with['signals']['em_dash_rate']['value']
        );
    }

    public function test_empty_text_does_not_error(): void
    {
        $result = $this->analyzer->analyze('');

        $this->assertSame([], $result['sentence_scores']);
        $this->assertArrayHasKey('signals', $result);
    }
}
