<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Gemini Pro Analyzer
 *
 * Fallback provider. Uses the Gemini generateContent API.
 * analyzePerformance() is included so Gemini can serve as a drop-in
 * fallback when Claude is unavailable.
 */
class GoogleGeminiProAnalyzer implements AiAnalysisContract
{
    protected string $apiKey;
    protected string $apiUrl;
    protected string $model;
    protected int    $maxTokens;
    protected int    $timeout;

    public function __construct()
    {
        $this->apiKey    = (string) (Setting::get('gemini_pro_api_key') ?? config('ai.providers.gemini_pro.api_key') ?? '');
        $this->apiUrl    = rtrim(Setting::get('gemini_pro_api_url', config('ai.providers.gemini_pro.api_url', 'https://generativelanguage.googleapis.com/v1beta')), '/');
        $this->model     = Setting::get('gemini_pro_model', config('ai.providers.gemini_pro.model', 'gemini-2.0-flash'));
        $this->maxTokens = (int) Setting::get('gemini_pro_max_tokens', config('ai.providers.gemini_pro.max_tokens', 8192));
        $this->timeout   = (int) Setting::get('gemini_pro_timeout', config('ai.providers.gemini_pro.timeout', 60));
    }

    // =========================================================================
    // AiAnalysisContract
    // =========================================================================

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getProvider(): string
    {
        return 'gemini_pro';
    }

    // -------------------------------------------------------------------------

    public function analyzeNarrative(string $narrative): array
    {
        $prompt = $this->buildNarrativePrompt($narrative);
        $raw    = $this->callApi($prompt);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $this->parseJson($raw['content']);
        if ($d === null) {
            return ['success' => false, 'error' => 'Failed to parse Gemini narrative response'];
        }

        return [
            'success'                => true,
            'provider'               => 'gemini_pro',
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
        $prompt = $this->buildConductPrompt($description, $type, $severity);
        $raw    = $this->callApi($prompt);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $this->parseJson($raw['content']);
        if ($d === null) {
            return ['success' => false, 'error' => 'Failed to parse Gemini conduct response'];
        }

        return [
            'success'                => true,
            'provider'               => 'gemini_pro',
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
        $prompt = $this->buildInterventionPrompt($evaluationData);
        $raw    = $this->callApi($prompt);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $this->parseJson($raw['content']);
        if ($d === null) {
            return ['success' => false, 'error' => 'Failed to parse Gemini intervention response'];
        }

        return [
            'success'             => true,
            'provider'            => 'gemini_pro',
            'model'               => $this->model,
            'interventions'       => $d['interventions']       ?? [],
            'support_measures'    => $d['support_measures']    ?? [],
            'follow_up_frequency' => $d['follow_up_frequency'] ?? 'monthly',
            'success_metrics'     => $d['success_metrics']     ?? [],
        ];
    }

    // -------------------------------------------------------------------------

    public function analyzePerformance(array $context): array
    {
        $prompt = $this->buildPerformancePrompt($context);
        $raw    = $this->callApi($prompt);

        if (!$raw['success']) {
            return ['success' => false, 'error' => $raw['error']];
        }

        $d = $this->parseJson($raw['content']);
        if ($d === null) {
            return ['success' => false, 'error' => 'Failed to parse Gemini performance response'];
        }

        return [
            'success'                  => true,
            'provider'                 => 'gemini_pro',
            'model'                    => $this->model,
            'summary_en'               => $d['summary_en']               ?? '',
            'summary_am'               => $d['summary_am']               ?? '',
            'kpi_scores_json'          => $d['kpi_scores_json']          ?? [],
            'sentiment'                => $d['sentiment']                ?? [],
            'risk_flags'               => $d['risk_flags']               ?? [],
            'strengths'                => $d['strengths']                ?? [],
            'development_areas'        => $d['development_areas']        ?? [],
            'interventions'            => $d['interventions']            ?? [],
            'increment_recommendation' => $d['increment_recommendation'] ?? [],
            'tokens_used'              => 0,
        ];
    }

    // =========================================================================
    // Core caller
    // =========================================================================

    protected function callApi(string $prompt): array
    {
        $url = "{$this->apiUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

        try {
            $response = Http::timeout($this->timeout)
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $this->maxTokens,
                        'temperature'     => 0.4,
                        'responseMimeType'=> 'application/json',
                    ],
                    'systemInstruction' => [
                        'parts' => [
                            ['text' => 'You are an expert HR analyst. Always respond with valid JSON only, no markdown fences, no explanations.'],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                $text = $response->json('candidates.0.content.parts.0.text');

                Log::channel('ai')->debug('Gemini API success', [
                    'provider'      => 'gemini_pro',
                    'prompt_length' => strlen($prompt),
                ]);

                return ['success' => true, 'content' => $text ?? ''];
            }

            $error = $response->json('error.message') ?? "HTTP {$response->status()}";

            Log::channel('ai')->error('Gemini API error', [
                'status'   => $response->status(),
                'error'    => $error,
                'provider' => 'gemini_pro',
            ]);

            return ['success' => false, 'error' => $error];

        } catch (\Exception $e) {
            Log::channel('ai')->error('Gemini API exception', [
                'error'    => $e->getMessage(),
                'provider' => 'gemini_pro',
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // =========================================================================
    // Prompt builders
    // =========================================================================

    protected function buildNarrativePrompt(string $narrative): string
    {
        return <<<PROMPT
Analyze the following employee narrative report. Return ONLY a JSON object:
{
  "summary_en": "...",
  "summary_am": "...",
  "kpi_scores": {"kpi_name": 0},
  "sentiment": {"overall": "positive|neutral|negative", "confidence": 0.95},
  "risk_flags": [],
  "strengths": [],
  "areas_for_improvement": []
}

NARRATIVE:
{$narrative}
PROMPT;
    }

    protected function buildConductPrompt(string $description, string $type, string $severity): string
    {
        return <<<PROMPT
Analyze this conduct issue. Return ONLY a JSON object:
{
  "severity_assessment": "minor|moderate|major|critical",
  "confidence": 0.95,
  "risk_level": "low|medium|high|critical",
  "suggested_actions": [],
  "escalation_needed": false,
  "investigation_required": false,
  "warnings": []
}

Issue Type: {$type}
Reported Severity: {$severity}
Description: {$description}
PROMPT;
    }

    protected function buildInterventionPrompt(array $evaluationData): string
    {
        $json = json_encode($evaluationData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Based on the evaluation data below, return ONLY a JSON object:
{
  "interventions": [{"type": "training|mentoring|counseling|performance_plan|other", "description": "...", "priority": "high|medium|low", "timeline": "immediate|2-4 weeks|1-3 months"}],
  "support_measures": [],
  "follow_up_frequency": "weekly|bi-weekly|monthly",
  "success_metrics": []
}

DATA:
{$json}
PROMPT;
    }

    protected function buildPerformancePrompt(array $context): string
    {
        $json = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Perform a comprehensive performance analysis for the employee below. Return ONLY a JSON object:
{
  "summary_en": "...",
  "summary_am": "...",
  "kpi_scores_json": [{"kpi_id": 1, "kpi_title": "...", "ai_score": 80, "evidence": "..."}],
  "sentiment": {"overall": "positive|neutral|negative", "confidence": 0.9, "detail": "..."},
  "risk_flags": [],
  "strengths": [],
  "development_areas": [],
  "interventions": [{"type": "training", "description": "...", "priority": "high", "timeline": "1-3 months"}],
  "increment_recommendation": {"justified": true, "rationale": "..."}
}

PERFORMANCE CONTEXT:
{$json}
PROMPT;
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Extract a JSON object from potentially fenced markdown output.
     */
    protected function parseJson(string $content): ?array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $clean = preg_replace('/```\s*$/m', '', $clean ?? $content);

        // Try direct decode first
        $decoded = json_decode(trim($clean), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Fallback: extract first {...} block
        preg_match('/\{.*\}/s', $content, $matches);
        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::channel('ai')->warning('Gemini JSON parse failed', ['preview' => substr($content, 0, 300)]);

        return null;
    }
}
