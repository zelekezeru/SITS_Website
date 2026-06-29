<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Providers Configuration
    |--------------------------------------------------------------------------
    | Configure API keys and endpoints for Claude and Google Gemini.
    |
    */

    'enabled' => env('AI_ENABLED', false),

    'providers' => [
        'claude_pro' => [
            'api_key'                => env('CLAUDE_PRO_API_KEY'),
            'api_url'                => env('CLAUDE_PRO_API_URL', 'https://api.anthropic.com/v1'),

            // Anthropic's most capable model. Override via CLAUDE_PRO_MODEL.
            'model'                  => env('CLAUDE_PRO_MODEL', 'claude-opus-4-8'),

            // Standard analysis (narrative, conduct, interventions)
            'max_tokens'             => 4096,

            // Deep, multi-parameter performance analysis needs more room + time
            'performance_max_tokens' => 16000,

            // Opus 4.8 uses adaptive thinking (Claude decides depth); budget_tokens
            // is rejected with a 400 on this model. Effort tunes reasoning depth:
            // low | medium | high | xhigh | max.
            'thinking'               => env('CLAUDE_PRO_THINKING', true),
            'effort'                 => env('CLAUDE_PRO_EFFORT', 'high'),

            // Schema-constrained JSON via output_config.format.
            'structured_outputs'     => env('CLAUDE_PRO_STRUCTURED', true),

            // HTTP timeout in seconds — adaptive thinking can take 30–90 s
            'timeout'                => 120,

            // Anthropic API version header
            'anthropic_version'      => '2023-06-01',
        ],

        'gemini_pro' => [
            'api_key'    => env('GEMINI_PRO_API_KEY'),
            'api_url'    => env('GEMINI_PRO_API_URL', 'https://generativelanguage.googleapis.com/v1beta'),
            'model'      => env('GEMINI_PRO_MODEL', 'gemini-2.0-flash'),
            'max_tokens' => 8192,
            'timeout'    => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    */
    'default' => env('AI_DEFAULT_PROVIDER', 'claude_pro'),

    /*
    |--------------------------------------------------------------------------
    | AI Analysis Settings
    |--------------------------------------------------------------------------
    */
    'analysis' => [
        'narrative' => [
            'enabled'            => env('AI_NARRATIVE_ANALYSIS', true),
            'extract_kpi_scores' => true,
            'sentiment_analysis' => true,
            'risk_flag_detection'=> true,
        ],

        'conduct' => [
            'enabled'             => env('AI_CONDUCT_ANALYSIS', true),
            'severity_prediction' => true,
            'recommendation'      => true,
        ],

        'performance' => [
            'enabled'          => env('AI_PERFORMANCE_ANALYSIS', true),
            'use_thinking'     => env('AI_PERFORMANCE_THINKING', true),
        ],

        'suggestions' => [
            'enabled'       => env('AI_SUGGESTIONS', true),
            'interventions' => true,
            'actions'       => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback & Error Handling
    |--------------------------------------------------------------------------
    */
    'fallback' => [
        'enabled'                => env('AI_FALLBACK_ENABLED', true),
        'use_cached_results'     => true,
        'notify_admin_on_failure'=> true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'max_attempts'      => 3,
        'delay_ms'          => 1000, // milliseconds; doubles on each attempt
        'exponential_backoff'=> true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled'             => true,
        'requests_per_minute' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Monitoring
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled'       => true,
        'log_requests'  => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', false), // careful with PII
        'log_errors'    => true,
    ],
];
