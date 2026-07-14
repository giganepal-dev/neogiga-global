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

    // Interim admin API gate (see App\Http\Middleware\EnsureAdminToken).
    // Unset = admin routes refuse all requests (fail closed).
    // Prefer ADMIN_API_TOKEN_HASH=sha256(token) in production.
    'admin_api_token' => env('ADMIN_API_TOKEN'),
    'admin_api_token_hash' => env('ADMIN_API_TOKEN_HASH'),
    'admin_api_token_permissions' => array_filter(array_map('trim', explode(',', (string) env('ADMIN_API_TOKEN_PERMISSIONS', '')))),

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
