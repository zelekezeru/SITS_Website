<?php

namespace App\Services\Integrity\Plagiarism;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Messages\WebSearchTool20260318;
use Anthropic\RequestOptions;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Claude's server-side web search tool for one passage
 * at a time. This is the low-level "ask Claude to search the web for this
 * exact phrasing" primitive — passage selection (which spans are worth
 * checking, Scripture/quote exclusion, cost-controlled batching, caching)
 * is WebMatcher's job (Phase 4), not this class's.
 *
 * Reuses the same Claude Pro provider config as every other AI call in
 * SITS — no second API key path.
 */
class ClaudeWebSearchClient
{
    protected string $apiKey;

    protected string $model;

    protected int $maxTokens;

    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = (string) self::setting('claude_pro_api_key', config('ai.providers.claude_pro.api_key', ''));
        $this->model = (string) self::setting('claude_pro_model', config('integrity.claude_model', 'claude-opus-5'));
        $this->maxTokens = 2048;
        $this->timeout = (int) self::setting('claude_pro_timeout', config('ai.providers.claude_pro.timeout', 120));
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
     * Search the web for a single distinctive passage and report back
     * whether a matching published source was found.
     *
     * @return array{success:bool, found?:bool, url?:?string, source_title?:?string, matched_excerpt?:?string, match_quality?:string, tokens_used?:int, error?:string}
     */
    public function checkPassage(string $passage): array
    {
        $params = [
            'maxTokens' => $this->maxTokens,
            'model' => $this->model,
            'system' => $this->systemPrompt(),
            'messages' => [
                ['role' => 'user', 'content' => $this->buildUserMessage($passage)],
            ],
            'tools' => [
                WebSearchTool20260318::with(maxUses: 3),
                $this->reportMatchTool(),
            ],
            'toolChoice' => ['type' => 'auto'],
        ];

        try {
            $client = new Client(
                apiKey: $this->apiKey,
                requestOptions: RequestOptions::with(
                    maxRetries: (int) config('ai.retry.max_attempts', 3),
                ),
            );

            $message = $client->messages->create(...$params);

            return static::parseSearchResult($message);
        } catch (RateLimitException $e) {
            Log::channel('ai')->error('Claude web search rate limited', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Claude API rate limit reached — please retry shortly.'];
        } catch (APIConnectionException $e) {
            Log::channel('ai')->error('Claude web search connection error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Could not reach the Claude API: '.$e->getMessage()];
        } catch (APIStatusException $e) {
            Log::channel('ai')->error('Claude web search status error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::channel('ai')->error('Claude web search exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Pure parsing of an SDK Message (or any duck-typed equivalent, which is
     * how this is unit-tested without a live API call) into the structured
     * match result. Ignores server_tool_use / web_search_tool_result blocks
     * — those are the model's search steps, not the final answer — and
     * looks for the `report_match` tool_use block it was instructed to call
     * once its search is done.
     *
     * @param  object{content: iterable, usage?: object}  $message
     * @return array{success:bool, found?:bool, url?:?string, source_title?:?string, matched_excerpt?:?string, match_quality?:string, tokens_used?:int, error?:string}
     */
    public static function parseSearchResult(object $message): array
    {
        $tokensUsed = ($message->usage->inputTokens ?? 0) + ($message->usage->outputTokens ?? 0);

        foreach ($message->content ?? [] as $block) {
            if (($block->type ?? '') === 'tool_use' && ($block->name ?? '') === 'report_match') {
                $input = (array) ($block->input ?? []);

                return [
                    'success' => true,
                    'found' => (bool) ($input['found'] ?? false),
                    'url' => $input['url'] ?? null,
                    'source_title' => $input['source_title'] ?? null,
                    'matched_excerpt' => $input['matched_excerpt'] ?? null,
                    'match_quality' => $input['match_quality'] ?? 'none',
                    'tokens_used' => $tokensUsed,
                ];
            }
        }

        return ['success' => false, 'error' => 'Claude did not report a match result for this passage.', 'tokens_used' => $tokensUsed];
    }

    protected function systemPrompt(): string
    {
        return <<<'SYSTEM'
You are a plagiarism-triage assistant for a theological seminary's academic integrity
review. You are given one short passage from a student paper and must check whether it
appears verbatim (or very closely) on the public web.

Guidelines:
- Use the web_search tool to search for the exact phrasing of the passage.
- A web match is a factual finding, not a verdict — you are NOT deciding whether this is
  plagiarism. Papers properly cite sources; a match to a cited source is expected and
  fine. Do not editorialize about academic dishonesty.
- If the passage is a Bible quotation, common idiom, or standard academic boilerplate,
  prefer match_quality 'none' unless the phrasing is unusually distinctive.
- After searching, you MUST call the report_match tool exactly once with your finding —
  never respond with plain text.
SYSTEM;
    }

    protected function buildUserMessage(string $passage): string
    {
        return <<<MSG
Search the web for this exact passage and report what you find by calling `report_match`.

PASSAGE:
"{$passage}"
MSG;
    }

    protected function reportMatchTool(): array
    {
        return [
            'name' => 'report_match',
            'description' => 'Report whether this passage was found on the public web.',
            'input_schema' => [
                'type' => 'object',
                'required' => ['found', 'match_quality'],
                'properties' => [
                    'found' => ['type' => 'boolean', 'description' => 'Whether a matching source was found.'],
                    'url' => ['type' => 'string', 'description' => 'URL of the matched source, if found.'],
                    'source_title' => ['type' => 'string', 'description' => 'Title of the matched page/source.'],
                    'matched_excerpt' => ['type' => 'string', 'description' => 'The exact excerpt from the source that matches.'],
                    'match_quality' => ['type' => 'string', 'enum' => ['exact', 'close', 'paraphrase', 'none']],
                ],
            ],
        ];
    }
}
