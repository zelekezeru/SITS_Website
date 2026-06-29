<?php

namespace App\Services\Ai;

use Anthropic\Client;
use Anthropic\RequestOptions;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * Claude AI Analyzer
 *
 * Uses the Anthropic Messages API with:
 *  - Structured tool-use for guaranteed-valid JSON outputs
 *  - Extended thinking (interleaved) for the deep analyzePerformance call
 *  - Exponential-backoff retry on transient errors
 *  - Bilingual (EN + AM) system persona
 */
class ClaudeProAnalyzer implements AiAnalysisContract
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int    $maxTokens;
    protected int    $performanceMaxTokens;
    protected bool   $thinkingEnabled;
    protected string $effort;
    protected int    $timeout;
    protected string $anthropicVersion;

    public function __construct()
    {
        $this->apiKey               = (string) (Setting::get('claude_pro_api_key') ?? config('ai.providers.claude_pro.api_key') ?? '');
        $this->apiUrl               = rtrim(Setting::get('claude_pro_api_url', config('ai.providers.claude_pro.api_url', 'https://api.anthropic.com/v1')), '/');
        $this->model                = Setting::get('claude_pro_model', config('ai.providers.claude_pro.model', 'claude-opus-4-8'));
        $this->maxTokens            = (int) Setting::get('claude_pro_max_tokens', config('ai.providers.claude_pro.max_tokens', 4096));
        $this->performanceMaxTokens = (int) Setting::get('claude_pro_performance_max_tokens', config('ai.providers.claude_pro.performance_max_tokens', 16000));
        $this->thinkingEnabled      = (bool) Setting::get('claude_pro_thinking', config('ai.providers.claude_pro.thinking', true));
        $this->effort               = (string) Setting::get('claude_pro_effort', config('ai.providers.claude_pro.effort', 'high'));
        $this->timeout              = (int) Setting::get('claude_pro_timeout', config('ai.providers.claude_pro.timeout', 120));
        $this->anthropicVersion     = Setting::get('claude_pro_anthropic_version', config('ai.providers.claude_pro.anthropic_version', '2023-06-01'));
    }

    // =========================================================================
    // AiAnalysisContract implementation
    // =========================================================================

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getProvider(): string
    {
        return 'claude_pro';
    }

    // -------------------------------------------------------------------------

    public function analyzeNarrative(string $narrative): array
    {
        $tool   = $this->narrativeOutputTool();
        $prompt = $this->buildNarrativeUserMessage($narrative);

        $raw = $this->callApi($prompt, $tool, useThinking: false);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $raw['tool_input'];

        return [
            'success'                => true,
            'provider'               => 'claude_pro',
            'model'                  => $this->model,
            'summary_en'             => $d['summary_en']             ?? '',
            'summary_am'             => $d['summary_am']             ?? '',
            'kpi_scores'             => $d['kpi_scores']             ?? [],
            'sentiment'              => $d['sentiment']              ?? [],
            'risk_flags'             => $d['risk_flags']             ?? [],
            'strengths'              => $d['strengths']              ?? [],
            'areas_for_improvement'  => $d['areas_for_improvement']  ?? [],
        ];
    }

    // -------------------------------------------------------------------------

    public function analyzeConductIssue(string $description, string $type, string $severity): array
    {
        $tool   = $this->conductOutputTool();
        $prompt = $this->buildConductUserMessage($description, $type, $severity);

        $raw = $this->callApi($prompt, $tool, useThinking: false);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $raw['tool_input'];

        return [
            'success'                => true,
            'provider'               => 'claude_pro',
            'model'                  => $this->model,
            'severity_assessment'    => $d['severity_assessment']    ?? '',
            'confidence'             => $d['confidence']             ?? 0.0,
            'risk_level'             => $d['risk_level']             ?? '',
            'suggested_actions'      => $d['suggested_actions']      ?? [],
            'escalation_needed'      => $d['escalation_needed']      ?? false,
            'investigation_required' => $d['investigation_required'] ?? false,
            'warnings'               => $d['warnings']               ?? [],
        ];
    }

    // -------------------------------------------------------------------------

    public function suggestInterventions(array $evaluationData): array
    {
        $tool   = $this->interventionOutputTool();
        $prompt = $this->buildInterventionUserMessage($evaluationData);

        $raw = $this->callApi($prompt, $tool, useThinking: false);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $raw['tool_input'];

        return [
            'success'              => true,
            'provider'             => 'claude_pro',
            'model'                => $this->model,
            'interventions'        => $d['interventions']        ?? [],
            'support_measures'     => $d['support_measures']     ?? [],
            'follow_up_frequency'  => $d['follow_up_frequency']  ?? 'monthly',
            'success_metrics'      => $d['success_metrics']      ?? [],
        ];
    }

    // -------------------------------------------------------------------------

    public function analyzePerformance(array $context): array
    {
        $tool   = $this->performanceOutputTool();
        $prompt = $this->buildPerformanceUserMessage($context);

        $useThinking = (bool) config('ai.analysis.performance.use_thinking', true);

        $raw = $this->callApi(
            $prompt,
            $tool,
            useThinking: $useThinking,
            maxTokens: $this->performanceMaxTokens
        );

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $raw['tool_input'];

        return [
            'success'                    => true,
            'provider'                   => 'claude_pro',
            'model'                      => $this->model,
            'summary_en'                 => $d['summary_en']                 ?? '',
            'summary_am'                 => $d['summary_am']                 ?? '',
            'kpi_scores_json'            => $d['kpi_scores_json']            ?? [],
            'sentiment'                  => $d['sentiment']                  ?? [],
            'risk_flags'                 => $d['risk_flags']                 ?? [],
            'strengths'                  => $d['strengths']                  ?? [],
            'development_areas'          => $d['development_areas']          ?? [],
            'interventions'              => $d['interventions']              ?? [],
            'increment_recommendation'   => $d['increment_recommendation']   ?? [],
            'tokens_used'                => $raw['tokens_used']              ?? 0,
        ];
    }

    // =========================================================================
    // Core API caller
    // =========================================================================

    /**
     * Call the Claude Messages API with tool-use output forcing.
     *
     * @param  string      $userMessage  The user-turn content.
     * @param  array       $tool         Tool definition (name + input_schema).
     * @param  bool        $useThinking  Whether to enable extended thinking.
     * @param  int|null    $maxTokens    Override max_tokens for this call.
     * @return array{success:bool, tool_input?:array, tokens_used?:int, error?:string}
     */
    protected function callApi(
        string $userMessage,
        array  $tool,
        bool   $useThinking = false,
        ?int   $maxTokens   = null
    ): array {
        $maxTok = $maxTokens ?? $this->maxTokens;

        $params = [
            'maxTokens'  => $maxTok,
            'model'      => $this->model,
            'system'     => $this->systemPrompt(),
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'tools'      => [$tool],
            'toolChoice' => ['type' => 'auto'],
        ];

        // Adaptive thinking: Claude decides how deeply to reason per request.
        // The legacy {type:"enabled", budget_tokens:N} form is rejected with a
        // 400 on Opus 4.8/4.7 — effort (low|medium|high|xhigh|max) tunes depth.
        // tool_choice stays "auto" (forcing a tool is incompatible with thinking).
        if ($useThinking && $this->thinkingEnabled) {
            $params['thinking']     = ['type' => 'adaptive'];
            $params['outputConfig'] = ['effort' => $this->effort];
        }

        try {
            // The official SDK transports over Guzzle and auto-retries
            // connection errors, 408/409/429, and >=500 with exponential backoff.
            $client = new Client(
                apiKey: $this->apiKey,
                requestOptions: RequestOptions::with(
                    maxRetries: (int) config('ai.retry.max_attempts', 3),
                ),
            );

            $message = $client->messages->create(...$params);

            return $this->extractToolUseResult($message);
        } catch (RateLimitException $e) {
            Log::channel('ai')->error('Claude API rate limited', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Claude API rate limit reached — please retry shortly.'];
        } catch (APIConnectionException $e) {
            Log::channel('ai')->error('Claude API connection error', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Could not reach the Claude API: ' . $e->getMessage()];
        } catch (APIStatusException $e) {
            Log::channel('ai')->error('Claude API status error', [
                'status' => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : null,
                'error'  => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::channel('ai')->error('Claude API exception', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract the tool_use block from a Claude SDK Message response.
     *
     * @param  \Anthropic\Messages\Message  $message
     */
    protected function extractToolUseResult($message): array
    {
        $tokensUsed = ($message->usage->inputTokens ?? 0) + ($message->usage->outputTokens ?? 0);
        $content    = $message->content ?? [];

        foreach ($content as $block) {
            if (($block->type ?? '') === 'tool_use') {
                Log::channel('ai')->debug('Claude tool_use success', [
                    'tool'        => $block->name ?? '',
                    'tokens_used' => $tokensUsed,
                    'provider'    => 'claude_pro',
                ]);

                return [
                    'success'     => true,
                    'tool_input'  => (array) ($block->input ?? []),
                    'tokens_used' => $tokensUsed,
                ];
            }
        }

        // Fallback: Claude returned text instead of a tool call — parse JSON out of it.
        $text = '';
        foreach ($content as $block) {
            if (($block->type ?? '') === 'text') {
                $text .= $block->text ?? '';
            }
        }

        Log::channel('ai')->warning('Claude returned text instead of tool_use', [
            'preview'     => substr($text, 0, 200),
            'tokens_used' => $tokensUsed,
        ]);

        preg_match('/\{.*\}/s', $text, $matches);
        if (! empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && \is_array($decoded)) {
                return [
                    'success'     => true,
                    'tool_input'  => $decoded,
                    'tokens_used' => $tokensUsed,
                ];
            }
        }

        return ['success' => false, 'error' => 'No structured tool_use output returned by Claude'];
    }

    // =========================================================================
    // System prompt
    // =========================================================================

    protected function systemPrompt(): string
    {
        return <<<SYSTEM
You are an expert HR analyst and performance management specialist for an Ethiopian higher-education institution.
You analyze employee performance data, narrative reports, and conduct issues with precision, fairness, and cultural sensitivity.

Guidelines:
- Always produce bilingual output: English (primary) and Amharic (summary).
- Be objective; base all assessments strictly on the evidence provided.
- Flag genuine risks concisely; do not manufacture concerns.
- KPI scores are 0–100; sentiment confidence is 0.0–1.0.
- You MUST use the provided tool to return your response — never return raw text.
SYSTEM;
    }

    // =========================================================================
    // User-message builders
    // =========================================================================

    protected function buildNarrativeUserMessage(string $narrative): string
    {
        return <<<MSG
Analyze the following employee narrative report and call the `submit_narrative_analysis` tool with your findings.

NARRATIVE REPORT:
{$narrative}
MSG;
    }

    protected function buildConductUserMessage(string $description, string $type, string $severity): string
    {
        return <<<MSG
Analyze the following conduct/disciplinary issue and call the `submit_conduct_analysis` tool with your assessment.

Issue Type: {$type}
Reported Severity: {$severity}
Description:
{$description}
MSG;
    }

    protected function buildInterventionUserMessage(array $evaluationData): string
    {
        $json = json_encode($evaluationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<MSG
Based on the following evaluation data, generate intervention and support recommendations.
Call the `submit_intervention_plan` tool with your plan.

EVALUATION DATA:
{$json}
MSG;
    }

    protected function buildPerformanceUserMessage(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<MSG
Perform a comprehensive performance analysis for the employee described below.
Use all available data — evaluation scores, KPI ratings, attendance, tasks, narratives,
and salary information — to produce deep, evidence-based insights.
Call the `submit_performance_analysis` tool with your complete findings.

PERFORMANCE CONTEXT:
{$json}
MSG;
    }

    // =========================================================================
    // Tool definitions (structured output schemas)
    // =========================================================================

    protected function narrativeOutputTool(): array
    {
        return [
            'name'         => 'submit_narrative_analysis',
            'description'  => 'Submit the structured analysis of the employee narrative report.',
            'input_schema' => [
                'type'       => 'object',
                'required'   => ['summary_en', 'summary_am', 'kpi_scores', 'sentiment', 'risk_flags', 'strengths', 'areas_for_improvement'],
                'properties' => [
                    'summary_en'            => ['type' => 'string', 'description' => 'Concise English summary (3–5 sentences).'],
                    'summary_am'            => ['type' => 'string', 'description' => 'Concise Amharic summary (3–5 sentences).'],
                    'kpi_scores'            => [
                        'type'                 => 'object',
                        'description'          => 'KPI name → inferred score (0–100) based on narrative evidence.',
                        'additionalProperties' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                    ],
                    'sentiment' => [
                        'type'       => 'object',
                        'required'   => ['overall', 'confidence'],
                        'properties' => [
                            'overall'    => ['type' => 'string', 'enum' => ['positive', 'neutral', 'negative']],
                            'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                        ],
                    ],
                    'risk_flags'            => ['type' => 'array', 'items' => ['type' => 'string']],
                    'strengths'             => ['type' => 'array', 'items' => ['type' => 'string']],
                    'areas_for_improvement' => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ];
    }

    protected function conductOutputTool(): array
    {
        return [
            'name'         => 'submit_conduct_analysis',
            'description'  => 'Submit the structured conduct issue assessment.',
            'input_schema' => [
                'type'       => 'object',
                'required'   => ['severity_assessment', 'confidence', 'risk_level', 'suggested_actions', 'escalation_needed', 'investigation_required', 'warnings'],
                'properties' => [
                    'severity_assessment'    => ['type' => 'string', 'enum' => ['minor', 'moderate', 'major', 'critical']],
                    'confidence'             => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                    'risk_level'             => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                    'suggested_actions'      => ['type' => 'array', 'items' => ['type' => 'string']],
                    'escalation_needed'      => ['type' => 'boolean'],
                    'investigation_required' => ['type' => 'boolean'],
                    'warnings'               => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ];
    }

    protected function interventionOutputTool(): array
    {
        return [
            'name'         => 'submit_intervention_plan',
            'description'  => 'Submit the structured intervention and support plan.',
            'input_schema' => [
                'type'       => 'object',
                'required'   => ['interventions', 'support_measures', 'follow_up_frequency', 'success_metrics'],
                'properties' => [
                    'interventions' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'required'   => ['type', 'description', 'priority', 'timeline'],
                            'properties' => [
                                'type'        => ['type' => 'string', 'enum' => ['training', 'mentoring', 'counseling', 'performance_plan', 'other']],
                                'description' => ['type' => 'string'],
                                'priority'    => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'timeline'    => ['type' => 'string', 'enum' => ['immediate', '2-4 weeks', '1-3 months', '3-6 months']],
                            ],
                        ],
                    ],
                    'support_measures'    => ['type' => 'array', 'items' => ['type' => 'string']],
                    'follow_up_frequency' => ['type' => 'string', 'enum' => ['weekly', 'bi-weekly', 'monthly', 'quarterly']],
                    'success_metrics'     => ['type' => 'array', 'items' => ['type' => 'string']],
                ],
            ],
        ];
    }

    protected function performanceOutputTool(): array
    {
        return [
            'name'         => 'submit_performance_analysis',
            'description'  => 'Submit the comprehensive performance analysis with all insights.',
            'input_schema' => [
                'type'       => 'object',
                'required'   => ['summary_en', 'summary_am', 'kpi_scores_json', 'sentiment', 'risk_flags', 'strengths', 'development_areas', 'interventions', 'increment_recommendation'],
                'properties' => [
                    'summary_en'    => ['type' => 'string', 'description' => 'Comprehensive English performance summary (5–8 sentences).'],
                    'summary_am'    => ['type' => 'string', 'description' => 'Comprehensive Amharic performance summary (5–8 sentences).'],
                    'kpi_scores_json' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'required'   => ['kpi_id', 'kpi_title', 'ai_score', 'evidence'],
                            'properties' => [
                                'kpi_id'    => ['type' => 'integer'],
                                'kpi_title' => ['type' => 'string'],
                                'ai_score'  => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                                'evidence'  => ['type' => 'string', 'description' => 'Quote or reasoning from available data.'],
                            ],
                        ],
                    ],
                    'sentiment' => [
                        'type'       => 'object',
                        'required'   => ['overall', 'confidence', 'detail'],
                        'properties' => [
                            'overall'    => ['type' => 'string', 'enum' => ['positive', 'neutral', 'negative']],
                            'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                            'detail'     => ['type' => 'string'],
                        ],
                    ],
                    'risk_flags'       => ['type' => 'array', 'items' => ['type' => 'string']],
                    'strengths'        => ['type' => 'array', 'items' => ['type' => 'string']],
                    'development_areas'=> ['type' => 'array', 'items' => ['type' => 'string']],
                    'interventions' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'required'   => ['type', 'description', 'priority', 'timeline'],
                            'properties' => [
                                'type'        => ['type' => 'string', 'enum' => ['training', 'mentoring', 'counseling', 'performance_plan', 'other']],
                                'description' => ['type' => 'string'],
                                'priority'    => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                                'timeline'    => ['type' => 'string', 'enum' => ['immediate', '2-4 weeks', '1-3 months', '3-6 months']],
                            ],
                        ],
                    ],
                    'increment_recommendation' => [
                        'type'       => 'object',
                        'required'   => ['justified', 'rationale'],
                        'properties' => [
                            'justified' => ['type' => 'boolean'],
                            'rationale' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
