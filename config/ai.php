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

            // Anthropic's current Opus. Override via CLAUDE_PRO_MODEL.
            'model'                  => env('CLAUDE_PRO_MODEL', 'claude-opus-5'),

            // Standard analysis (narrative, conduct, interventions).
            // On Opus 5 max_tokens caps thinking + response text together, so
            // this has headroom above the ~1-2k the tool payloads actually need.
            'max_tokens'             => 8192,

            // Deep, multi-parameter performance analysis needs more room + time
            'performance_max_tokens' => 16000,

            // Adaptive thinking (Claude decides depth per request); budget_tokens
            // is rejected with a 400 on this model. Effort tunes depth:
            // low | medium | high | xhigh | max.
            //
            // Thinking stays ON for every call. Disabling it on Opus 5 can make
            // the model emit a tool call as plain text — the call silently never
            // runs — which would break the structured-output contract this whole
            // class depends on. Cost is controlled with effort instead.
            'thinking'               => env('CLAUDE_PRO_THINKING', true),
            'effort'                 => env('CLAUDE_PRO_EFFORT', 'high'),

            // Effort for the light extraction calls (narrative, conduct,
            // interventions). Opus 5 performs strongly at low/medium.
            'light_effort'           => env('CLAUDE_PRO_LIGHT_EFFORT', 'low'),

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
        // Off: Claude is the only provider. With this on, a failed Claude call
        // would divert to whatever else is registered — in local that means the
        // MockAnalyzer, which returns fabricated analysis that looks real.
        'enabled'                => env('AI_FALLBACK_ENABLED', false),
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
