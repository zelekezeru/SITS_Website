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

];
