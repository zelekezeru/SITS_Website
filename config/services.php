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
    | HikVision Access Control — real-time attendance webhook
    |--------------------------------------------------------------------------
    | The terminal POSTs each authenticated punch to /hikvision/webhook. Because
    | that endpoint is public, it is guarded by a shared secret the device must
    | present (as ?token=, an X-Webhook-Token header, or the password half of
    | HTTP Basic auth) and, optionally, an IP allow-list.
    |
    | Event filtering keeps payroll data clean: only genuine access-granted
    | punches are stored. Door/exit-button, tamper and heartbeat events carry no
    | employee number and are dropped; non-access "major" event types are
    | dropped; and rapid duplicate scans (e.g. face + card for one entry) are
    | de-duplicated. Tighten `granted_sub_event_types` once you see your own
    | device's codes in the attendance_logs.raw_payload column.
    */
    'hikvision' => [
        // Shared secret the device must present. Leave blank to DISABLE the
        // check — not recommended on a public server, the endpoint is open.
        'webhook_secret' => env('HIKVISION_WEBHOOK_SECRET', ''),

        // Optional comma-separated allow-list of device source IPs. Empty = any.
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HIKVISION_WEBHOOK_IPS', ''))
        ))),

        // AccessControllerEvent.majorEventType values that count as access
        // events (5 = authentication event). Others (1=alarm, 2=exception,
        // 3=operation…) are ignored. Empty = accept any major type.
        'major_event_types' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HIKVISION_MAJOR_EVENT_TYPES', '5'))
        ))),

        // Optional allow-list of subEventType codes that mean "authentication
        // passed" on YOUR firmware (e.g. 1=card, 38=fingerprint, 75=face).
        // Empty = accept any subEventType (the employeeNo + major-type filters
        // already exclude anonymous/failed attempts).
        'granted_sub_event_types' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('HIKVISION_GRANTED_SUB_EVENT_TYPES', ''))
        ))),

        // Seconds within which a repeat punch (same employee + device +
        // direction) is treated as a duplicate and skipped. 0 = never dedupe.
        'dedup_seconds' => (int) env('HIKVISION_DEDUP_SECONDS', 60),
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
