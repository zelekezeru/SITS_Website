<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moodle LMS Integration
    |--------------------------------------------------------------------------
    | Used for SSO (Single Sign-On) between sits.edu.et and lms.sits.edu.et.
    | Generate the token in Moodle: Admin → Web Services → Manage Tokens.
    | The auth_userkey plugin must be installed on the Moodle instance.
    */
    'moodle' => [
        'url'     => env('MOODLE_URL', 'https://lms.sits.edu.et'),
        'token'   => env('MOODLE_TOKEN', ''),
        'service' => env('MOODLE_SSO_SERVICE', 'sits_sso_service'),
    ],

    'jstore' => [
        'url' => env('JSTORE_URL', 'https://library.sits.edu.et'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SITS Library — payments & AI (merged from sits-library)
    |--------------------------------------------------------------------------
    */

    // Chapa payment aggregator (Telebirr, CBE Birr, cards) for library fines.
    // With no keys set, the library falls back to the ManualGateway.
    'chapa' => [
        'secret' => env('CHAPA_SECRET_KEY'),
        'public' => env('CHAPA_PUBLIC_KEY'),
    ],

    // OpenAI-compatible endpoint the library's cataloging/semantic-search uses.
    // Point `base` at Gemini's OpenAI-compat endpoint or a Claude proxy to use those.
    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'base'  => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    // Smart AI providers (Claude + Gemini) for enrichment, semantic search,
    // recommendations & summaries. Disabled until keys are set.
    'ai' => [
        'default' => env('LIBRARY_AI_PROVIDER', 'claude'),
        'claude' => [
            'key'   => env('CLAUDE_PRO_API_KEY'),
            'model' => env('CLAUDE_PRO_MODEL', 'claude-opus-4-8'),
            'base'  => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        ],
        'gemini' => [
            'key'   => env('GEMINI_PRO_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base'  => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta/openai'),
        ],
    ],

];
