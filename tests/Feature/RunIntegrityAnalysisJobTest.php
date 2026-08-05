<?php

namespace Tests\Feature;

use App\Enums\IntegrityDocumentStatus;
use App\Enums\IntegrityReviewStatus;
use App\Jobs\RunIntegrityAnalysis;
use App\Models\IntegrityDocument;
use App\Models\User;
use App\Notifications\IntegrityAnalysisFailed;
use App\Services\Integrity\Detection\ClaudeDetectionDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RunIntegrityAnalysisJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.providers.claude_pro.api_key' => 'test-key']);
    }

    private function fakeMessage(array $input, int $inputTokens = 500, int $outputTokens = 150): object
    {
        return (object) [
            'content' => [(object) ['type' => 'tool_use', 'name' => 'submit_detection_analysis', 'input' => $input]],
            'usage' => (object) ['inputTokens' => $inputTokens, 'outputTokens' => $outputTokens],
        ];
    }

    private function bindFakeClaudeDriver(object $message): void
    {
        $driver = \Mockery::mock(ClaudeDetectionDriver::class.'[callClaude]');
        $driver->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('callClaude')->andReturn($message);

        $this->app->instance(ClaudeDetectionDriver::class, $driver);
    }

    private function aiLikeText(): string
    {
        $sentence = 'Furthermore, this particular analysis demonstrates exactly ten simple words.';

        return implode(' ', array_fill(0, 45, $sentence));
    }

    private function humanLikeText(): string
    {
        $paragraphs = [
            "I honestly wasn't sure what to expect when I started this project. My professor kept "
                .'pushing me to dig deeper, and — after a few false starts, a lot of coffee, and one very '
                .'late night — I think I finally found an angle that felt like mine.',
            'Some parts still bug me, though; the third chapter especially! In my experience, that kind '
                ."of doubt never fully goes away, and honestly? Maybe it shouldn't. My advisor laughed "
                .'when I told her that, which I did not expect at all.',
            "Anyway, I've rewritten the introduction four times now. Each version taught me something "
                .'different about what I was actually trying to say. I still don\'t love it, but I think '
                .'it works, and that has to be good enough for now.',
            'The hardest part, honestly, was chapter two. I kept getting stuck on how to frame the '
                .'argument without sounding like I was just repeating the textbook. My roommate finally '
                .'told me to stop overthinking it and just write like I talk, which — annoyingly — helped.',
            'I still remember sitting in the library at two in the morning, completely sure I had picked '
                .'the wrong topic. Turns out I hadn\'t. It just took a while for the pieces to click, and '
                .'a lot of scratched-out notes before any of it made sense to me.',
            'My favorite part ended up being the conclusion, which is weird because I usually hate '
                .'writing those. This time it just came together — maybe because by then I actually knew '
                .'what I thought, instead of guessing at what I was supposed to think.',
            'One thing I\'d tell my past self: talk to your advisor earlier. I sat on my confusion for '
                .'weeks before finally asking a question that took her five minutes to answer. Pride is '
                .'expensive when you\'re also on a deadline.',
            'By the end, I was genuinely surprised how much I enjoyed the research itself, even the '
                .'tedious parts. There\'s something satisfying about finally understanding a source you\'d '
                .'read three times before without it clicking.',
        ];

        return implode("\n\n", $paragraphs);
    }

    public function test_ai_like_document_scores_at_or_above_70(): void
    {
        $this->bindFakeClaudeDriver($this->fakeMessage([
            'overall_assessment' => 'likely_ai',
            'probability' => 92,
            'confidence' => 'high',
            'reasoning_summary' => 'Highly uniform sentence rhythm and generic transitions throughout.',
            'sentence_flags' => [],
            'style_observations' => ['formulaic structure'],
        ]));

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $this->aiLikeText(),
            'word_count' => str_word_count($this->aiLikeText()),
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        $document->refresh();
        $report = $document->report;

        $this->assertSame(IntegrityDocumentStatus::COMPLETE, $document->status);
        $this->assertNotNull($report);
        $this->assertGreaterThanOrEqual(70, $report->ai_probability);
    }

    public function test_human_like_document_scores_at_or_below_40(): void
    {
        $this->bindFakeClaudeDriver($this->fakeMessage([
            'overall_assessment' => 'likely_human',
            'probability' => 6,
            'confidence' => 'high',
            'reasoning_summary' => 'Distinct personal voice and varied sentence rhythm.',
            'sentence_flags' => [],
            'style_observations' => [],
        ]));

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $this->humanLikeText(),
            'word_count' => str_word_count($this->humanLikeText()),
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        $document->refresh();
        $report = $document->report;

        $this->assertSame(IntegrityDocumentStatus::COMPLETE, $document->status);
        $this->assertNotNull($report);
        $this->assertLessThanOrEqual(40, $report->ai_probability);
    }

    public function test_sentence_scores_length_matches_sentence_count(): void
    {
        $text = $this->humanLikeText();
        $this->bindFakeClaudeDriver($this->fakeMessage([
            'overall_assessment' => 'mixed',
            'probability' => 50,
            'confidence' => 'medium',
            'reasoning_summary' => '',
            'sentence_flags' => [],
            'style_observations' => [],
        ]));

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $text,
            'word_count' => str_word_count($text),
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        preg_match_all('/[^.!?]+[.!?]+/u', $text, $matches);
        $expectedSentenceCount = count($matches[0]);

        $this->assertCount($expectedSentenceCount, $document->refresh()->report->sentence_scores);
    }

    public function test_a_long_non_latin_script_document_is_still_chunked_correctly(): void
    {
        // str_word_count() is ASCII-only and returns ~0 for non-Latin script —
        // if chunking used it, a long Amharic document would never be judged
        // "long enough" to split, and would be sent to Claude as a single
        // oversized request instead of being chunked like any other
        // long document. Word count here is real (space-separated tokens),
        // just not Latin script.
        config(['integrity.chunk_trigger_words' => 6000, 'integrity.chunk_words' => 4000]);

        // Chunking splits on paragraph boundaries (\n\n), so the fixture needs
        // real paragraphs, not one continuous run — same as any real essay.
        $amharicParagraph = implode(' ', array_fill(0, 500, 'ቃል'));
        $longText = implode("\n\n", array_fill(0, 14, $amharicParagraph));

        $driver = \Mockery::mock(ClaudeDetectionDriver::class.'[callClaude]');
        $driver->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('callClaude')
            ->atLeast()->times(2) // must be split into more than one Claude call
            ->andReturn($this->fakeMessage([
                'overall_assessment' => 'mixed', 'probability' => 50, 'confidence' => 'medium',
                'reasoning_summary' => '', 'sentence_flags' => [], 'style_observations' => [],
            ]));
        $this->app->instance(ClaudeDetectionDriver::class, $driver);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $longText,
            'word_count' => 7000,
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        $this->assertSame(IntegrityDocumentStatus::COMPLETE, $document->refresh()->status);
    }

    public function test_a_completed_analysis_writes_an_analyze_audit_row(): void
    {
        $this->bindFakeClaudeDriver($this->fakeMessage([
            'overall_assessment' => 'mixed', 'probability' => 50, 'confidence' => 'medium',
            'reasoning_summary' => '', 'sentence_flags' => [], 'style_observations' => [],
        ]));

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $this->humanLikeText(),
            'word_count' => str_word_count($this->humanLikeText()),
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        $report = $document->refresh()->report;
        $this->assertDatabaseHas('integrity_audit_log', [
            'report_id' => $report->id,
            'user_id' => $document->instructor_id,
            'action' => 'analyze',
        ]);
    }

    public function test_insufficient_text_below_min_words_skips_analysis_and_completes_with_that_verdict(): void
    {
        $document = IntegrityDocument::factory()->create([
            'extracted_text' => 'Too short to analyze meaningfully.',
            'word_count' => 6,
        ]);

        $job = new RunIntegrityAnalysis($document);
        $this->app->call([$job, 'handle']);

        $document->refresh();

        $this->assertSame(IntegrityDocumentStatus::COMPLETE, $document->status);
        $this->assertSame('insufficient_text', $document->report->verdict_label->value);
        $this->assertNull($document->report->ai_probability);
        $this->assertSame(IntegrityReviewStatus::NONE, $document->report->review_status);
    }

    public function test_a_failing_claude_call_throws_so_the_queue_will_retry_the_job(): void
    {
        $driver = \Mockery::mock(ClaudeDetectionDriver::class.'[callClaude]');
        $driver->shouldAllowMockingProtectedMethods();
        $driver->shouldReceive('callClaude')->andThrow(new \RuntimeException('simulated network failure'));
        $this->app->instance(ClaudeDetectionDriver::class, $driver);

        $document = IntegrityDocument::factory()->create([
            'extracted_text' => $this->humanLikeText(),
            'word_count' => str_word_count($this->humanLikeText()),
        ]);

        $job = new RunIntegrityAnalysis($document);

        $this->expectException(\RuntimeException::class);
        $this->app->call([$job, 'handle']);
    }

    public function test_failed_hook_marks_document_failed_and_notifies_the_instructor(): void
    {
        Notification::fake();

        $instructor = User::factory()->create();
        $document = IntegrityDocument::factory()->create(['instructor_id' => $instructor->id]);

        $job = new RunIntegrityAnalysis($document);
        $job->failed(new \RuntimeException('Claude detection pass failed: simulated network failure'));

        $document->refresh();
        $this->assertSame(IntegrityDocumentStatus::FAILED, $document->status);
        $this->assertStringContainsString('simulated network failure', $document->failure_reason);

        Notification::assertSentTo($instructor, IntegrityAnalysisFailed::class);
    }
}
