<?php

namespace App\Services\Integrity\Detection;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\RequestOptions;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Claude analysis pass for AI-detection (Phase 3.2). Mirrors the existing
 * ClaudeProAnalyzer/ClaudeWebSearchClient pattern in this codebase: forced
 * structured tool-use output, same provider config, same retry handling.
 *
 * `callClaude()` is the one seam kept thin on purpose — tests override it
 * (partial mock) to inject a fake SDK Message rather than hitting the API,
 * matching how this repo tests AI call sites elsewhere (MockAnalyzer).
 */
class ClaudeDetectionDriver
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
     * @return array{success:bool, overall_assessment?:string, probability?:int, confidence?:string, reasoning_summary?:string, sentence_flags?:list<array{index:int,reason:string}>, style_observations?:list<string>, tokens_used?:int, error?:string}
     */
    public function analyze(string $text): array
    {
        $params = [
            'maxTokens' => $this->maxTokens,
            'model' => $this->model,
            'system' => $this->systemPrompt(),
            'messages' => [
                ['role' => 'user', 'content' => $this->buildUserMessage($text)],
            ],
            'tools' => [$this->outputTool()],
            'toolChoice' => ['type' => 'auto'],
        ];

        try {
            $message = $this->callClaude($params);

            return static::parseAnalysisResult($message);
        } catch (RateLimitException $e) {
            Log::channel('ai')->error('Claude detection rate limited', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Claude API rate limit reached — please retry shortly.'];
        } catch (APIConnectionException $e) {
            Log::channel('ai')->error('Claude detection connection error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Could not reach the Claude API: '.$e->getMessage()];
        } catch (APIStatusException $e) {
            Log::channel('ai')->error('Claude detection status error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::channel('ai')->error('Claude detection exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
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

    /**
     * Pure parsing of an SDK Message (or duck-typed equivalent) into the
     * structured detection result — unit-testable without a live API call.
     */
    public static function parseAnalysisResult(object $message): array
    {
        $tokensUsed = ($message->usage->inputTokens ?? 0) + ($message->usage->outputTokens ?? 0);

        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? '') === 'tool_use' && ($block->name ?? '') === 'submit_detection_analysis') {
                $input = (array) ($block->input ?? []);

                return [
                    'success' => true,
                    'overall_assessment' => $input['overall_assessment'] ?? 'mixed',
                    'probability' => (int) ($input['probability'] ?? 50),
                    'confidence' => $input['confidence'] ?? 'low',
                    'reasoning_summary' => $input['reasoning_summary'] ?? '',
                    'sentence_flags' => $input['sentence_flags'] ?? [],
                    'style_observations' => $input['style_observations'] ?? [],
                    'tokens_used' => $tokensUsed,
                ];
            }
        }

        return ['success' => false, 'error' => 'No structured detection output returned by Claude.', 'tokens_used' => $tokensUsed];
    }

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
You are an AI-writing-detection assistant for a theological seminary's academic
integrity review. You assess whether a piece of student writing shows signs of being
AI-generated. Your assessment is an advisory triage signal for a human instructor —
never a verdict.

Guidelines:
- Weigh theological and academic register correctly: formal tone, structured argument,
  and citation-heavy writing are normal for graduate seminary work and are NOT evidence
  of AI generation on their own.
- Most students are ESL (English as a second language) writers. Non-native phrasing,
  simplified sentence structure, or unusual word choice must NOT be treated as AI
  evidence — ESL writers are already statistically more likely to be falsely flagged,
  and your job is to avoid making that worse.
- Base your assessment on genuine AI-writing patterns: unnaturally uniform rhythm,
  generic filler transitions, absence of any personal voice, and generic non-specific
  claims — not on formality or non-native English.
- You MUST call the submit_detection_analysis tool with your findings — never respond
  with plain text.
SYSTEM;
    }

    protected function buildUserMessage(string $text): string
    {
        return <<<MSG
Analyze the following student paper and call `submit_detection_analysis` with your
assessment.

PAPER:
{$text}
MSG;
    }

    protected function outputTool(): array
    {
        return [
            'name' => 'submit_detection_analysis',
            'description' => 'Submit the structured AI-writing-detection assessment.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['overall_assessment', 'probability', 'confidence', 'reasoning_summary', 'sentence_flags', 'style_observations'],
                'properties' => [
                    'overall_assessment' => ['type' => 'string', 'enum' => ['likely_human', 'mixed', 'likely_ai']],
                    'probability' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100, 'description' => 'Estimated probability (0-100) the text is AI-generated.'],
                    'confidence' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                    'reasoning_summary' => ['type' => 'string'],
                    'sentence_flags' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'required' => ['index', 'reason'],
                            'properties' => [
                                'index' => ['type' => 'integer'],
                                'reason' => ['type' => 'string'],
                            ],
                        ],
                    ],
                    'style_observations' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ];
    }
}
