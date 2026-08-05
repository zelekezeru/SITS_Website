<?php

namespace App\Services\Integrity\Writing;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\RequestOptions;
use App\Enums\WritingReportType;
use App\Models\IntegrityDocument;
use App\Models\IntegrityReport;
use App\Models\Setting;
use App\Models\WritingReport;
use Illuminate\Support\Facades\Log;

/**
 * Claude-powered writing tools (Phase 5): grammar, summarize, factCheck,
 * feedbackAssistant. Each persists a WritingReport and mirrors the same
 * structured tool-use pattern as ClaudeDetectionDriver.
 *
 * On a malformed response (no matching tool_use block), the call is
 * retried once before giving up — a transient "Claude answered in plain
 * text" slip is common enough to be worth one automatic retry rather than
 * failing the instructor's request outright.
 */
class WritingToolsService
{
    protected string $apiKey;

    protected string $model;

    protected int $maxTokens;

    public function __construct()
    {
        $this->apiKey = (string) self::setting('claude_pro_api_key', config('ai.providers.claude_pro.api_key', ''));
        $this->model = (string) self::setting('claude_pro_model', config('integrity.claude_model', 'claude-opus-5'));
        $this->maxTokens = 4096;
    }

    protected static function setting(string $key, mixed $fallback = null): mixed
    {
        $value = Setting::get($key);

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return $fallback;
        }

        return $value;
    }

    public function isAvailable(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * @return array{success:bool, report?:WritingReport, payload?:array, error?:string}
     */
    public function grammar(IntegrityDocument $document): array
    {
        return $this->run(
            $document,
            WritingReportType::GRAMMAR,
            'submit_grammar_suggestions',
            [
                'type' => 'object',
                'required' => ['suggestions'],
                'properties' => [
                    'suggestions' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['start', 'end', 'original', 'suggestion', 'category', 'severity'],
                            'properties' => [
                                'start' => ['type' => 'integer'],
                                'end' => ['type' => 'integer'],
                                'original' => ['type' => 'string'],
                                'suggestion' => ['type' => 'string'],
                                'category' => ['type' => 'string', 'enum' => ['grammar', 'clarity', 'style', 'citation']],
                                'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                            ],
                        ],
                    ],
                ],
            ],
            'You proofread graduate seminary papers. Identify grammar, clarity, style, and citation issues. '
                .'Character offsets (start/end) must be relative to the paper text as given. Do not flag non-native '
                .'phrasing that is grammatically correct — ESL students should not be penalized for register alone. '
                .'Call submit_grammar_suggestions with your findings.',
            fn (string $text) => "Proofread this paper and call submit_grammar_suggestions.\n\nPAPER:\n{$text}",
            fn (array $input) => $input['suggestions'] ?? [],
        );
    }

    /**
     * @return array{success:bool, report?:WritingReport, payload?:array, error?:string}
     */
    public function summarize(IntegrityDocument $document): array
    {
        return $this->run(
            $document,
            WritingReportType::SUMMARY,
            'submit_summary',
            [
                'type' => 'object',
                'required' => ['abstract', 'key_claims', 'suggested_title'],
                'properties' => [
                    'abstract' => ['type' => 'string', 'description' => 'Approximately 150 words.'],
                    'key_claims' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'suggested_title' => ['type' => 'string'],
                ],
            ],
            'You write concise academic abstracts for graduate seminary papers. Call submit_summary with a '
                .'~150-word abstract, a bullet list of key claims, and a suggested title.',
            fn (string $text) => "Summarize this paper and call submit_summary.\n\nPAPER:\n{$text}",
            fn (array $input) => [
                'abstract' => $input['abstract'] ?? '',
                'key_claims' => $input['key_claims'] ?? [],
                'suggested_title' => $input['suggested_title'] ?? '',
            ],
        );
    }

    /**
     * Advisory only — flags claims worth a closer look, never marks
     * anything as factually wrong.
     *
     * @return array{success:bool, report?:WritingReport, payload?:array, error?:string}
     */
    public function factCheck(IntegrityDocument $document): array
    {
        return $this->run(
            $document,
            WritingReportType::FACTCHECK,
            'submit_fact_check',
            [
                'type' => 'object',
                'required' => ['claims'],
                'properties' => [
                    'claims' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['claim', 'checkability', 'note'],
                            'properties' => [
                                'claim' => ['type' => 'string'],
                                'checkability' => ['type' => 'string', 'enum' => ['verifiable', 'opinion', 'theological_position']],
                                'note' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'You identify checkable factual claims in graduate seminary papers, purely advisory — you never '
                .'assert a claim is true or false, only whether it is verifiable, an opinion, or a theological '
                .'position (which is not fact-checkable by nature). Call submit_fact_check with your findings.',
            fn (string $text) => "Identify checkable claims in this paper and call submit_fact_check.\n\nPAPER:\n{$text}",
            fn (array $input) => $input['claims'] ?? [],
        );
    }

    /**
     * Replaces the excluded "humanizer" — drafts constructive, pastoral
     * feedback for the instructor to review and edit. Never sent
     * automatically; persisting the draft is the full extent of this
     * method's effect.
     *
     * @return array{success:bool, report?:WritingReport, payload?:array, error?:string}
     */
    public function feedbackAssistant(IntegrityDocument $document, ?IntegrityReport $report, string $instructorNotes): array
    {
        $reportContext = $report
            ? "AI-detection verdict: {$report->verdict_label?->value}, confidence: {$report->confidence?->value}."
            : 'No integrity report available yet.';

        return $this->run(
            $document,
            WritingReportType::FEEDBACK,
            'submit_feedback_draft',
            [
                'type' => 'object',
                'required' => ['draft'],
                'properties' => [
                    'draft' => ['type' => 'string'],
                ],
            ],
            'You draft constructive, formative feedback from an instructor to a seminary student, in a warm, '
                .'pastoral tone. Never accuse the student of misconduct — a detection score is advisory only. '
                .'Frame concerns as questions and opportunities to discuss, not conclusions. This is a DRAFT the '
                .'instructor will review and edit before sharing, never sent automatically. '
                .'Call submit_feedback_draft with your draft.',
            fn (string $text) => "Draft feedback for this student paper.\n\n{$reportContext}\n\n"
                ."INSTRUCTOR'S ROUGH NOTES:\n{$instructorNotes}\n\nPAPER EXCERPT:\n".mb_substr($text, 0, 4000),
            fn (array $input) => ['draft' => $input['draft'] ?? ''],
        );
    }

    // =========================================================================
    // Shared plumbing
    // =========================================================================

    protected function run(
        IntegrityDocument $document,
        WritingReportType $type,
        string $toolName,
        array $inputSchema,
        string $systemPrompt,
        \Closure $buildUserMessage,
        \Closure $normalizePayload,
    ): array {
        $text = (string) $document->extracted_text;
        if (trim($text) === '') {
            return ['success' => false, 'error' => 'Document has no extracted text.'];
        }

        $tool = [
            'name' => $toolName,
            'description' => "Submit structured {$type->value} output.",
            'input_schema' => $inputSchema,
        ];

        $result = $this->callWithRetry($systemPrompt, $buildUserMessage($text), $tool);

        if (! $result['success']) {
            return $result;
        }

        $payload = $normalizePayload($result['tool_input']);

        $writingReport = WritingReport::updateOrCreate(
            ['integrity_document_id' => $document->id, 'type' => $type],
            [
                'payload' => $payload,
                'model' => $this->model,
                'token_usage' => ['tokens_used' => $result['tokens_used'] ?? 0],
            ]
        );

        return ['success' => true, 'report' => $writingReport, 'payload' => $payload];
    }

    /**
     * Only retries the "Claude answered but didn't call the tool" case.
     * Rate-limit/connection/status failures are NOT retried here — the SDK
     * client already retries those internally (`maxRetries` in callClaude),
     * so retrying again at this layer would just be a second wasted paid
     * call stacked on top of retries that already happened and already
     * failed.
     */
    protected function callWithRetry(string $systemPrompt, string $userMessage, array $tool): array
    {
        $result = $this->attempt($systemPrompt, $userMessage, $tool);
        if ($result['success'] || ! ($result['retryable'] ?? true)) {
            return $result;
        }

        Log::channel('ai')->warning('Writing tool response malformed, retrying once', ['tool' => $tool['name']]);

        return $this->attempt($systemPrompt, $userMessage, $tool);
    }

    protected function attempt(string $systemPrompt, string $userMessage, array $tool): array
    {
        $params = [
            'maxTokens' => $this->maxTokens,
            'model' => $this->model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'tools' => [$tool],
            'toolChoice' => ['type' => 'auto'],
        ];

        try {
            $message = $this->callClaude($params);

            return static::parseToolResult($message, $tool['name']);
        } catch (RateLimitException $e) {
            Log::channel('ai')->error('Writing tool rate limited', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Claude API rate limit reached — please retry shortly.', 'retryable' => false];
        } catch (APIConnectionException $e) {
            Log::channel('ai')->error('Writing tool connection error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Could not reach the Claude API: '.$e->getMessage(), 'retryable' => false];
        } catch (APIStatusException $e) {
            Log::channel('ai')->error('Writing tool status error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage(), 'retryable' => false];
        } catch (\Throwable $e) {
            Log::channel('ai')->error('Writing tool exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage(), 'retryable' => false];
        }
    }

    protected function callClaude(array $params): object
    {
        $client = new Client(
            apiKey: $this->apiKey,
            requestOptions: RequestOptions::with(
                maxRetries: (int) config('ai.retry.max_attempts', 3),
            ),
        );

        return $client->messages->create(...$params);
    }

    public static function parseToolResult(object $message, string $toolName): array
    {
        $tokensUsed = ($message->usage->inputTokens ?? 0) + ($message->usage->outputTokens ?? 0);

        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? '') === 'tool_use' && ($block->name ?? '') === $toolName) {
                return [
                    'success' => true,
                    'tool_input' => (array) ($block->input ?? []),
                    'tokens_used' => $tokensUsed,
                ];
            }
        }

        return ['success' => false, 'error' => "Claude did not call {$toolName}.", 'tokens_used' => $tokensUsed];
    }
}
