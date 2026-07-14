<?php

return [
    'email' => [
        'provider' => env('MARKETING_EMAIL_PROVIDER', 'sandbox'),
        'api_base_url' => env('MARKETING_EMAIL_API_BASE_URL'),
        'api_key' => env('MARKETING_EMAIL_API_KEY'),
        'account_id' => env('MARKETING_EMAIL_ACCOUNT_ID'),
        'webhook_secret' => env('MARKETING_EMAIL_WEBHOOK_SECRET'),
        'timeout' => (int) env('MARKETING_EMAIL_TIMEOUT', 30),
        'test_mode' => env('MARKETING_EMAIL_TEST_MODE', true),
        'sending_enabled' => env('MARKETING_EMAIL_SENDING_ENABLED', false),
        'approval_required' => env('MARKETING_EMAIL_APPROVAL_REQUIRED', true),
        'test_recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('MARKETING_EMAIL_TEST_RECIPIENTS', ''))))),
        'queue' => env('MARKETING_EMAIL_QUEUE', 'marketing'),
        'preparation_queue' => env('MARKETING_PREPARATION_QUEUE', 'campaign-preparation'),
        'rate_limit_per_minute' => (int) env('MARKETING_EMAIL_RATE_LIMIT_PER_MINUTE', 60),
        'daily_limit' => (int) env('MARKETING_EMAIL_DAILY_LIMIT', 5000),
    ],
    'transactional' => [
        'enabled' => env('TRANSACTIONAL_EMAIL_ENABLED', false),
        'mailer' => env('TRANSACTIONAL_MAILER', env('MAIL_MAILER', 'log')),
        'test_mode' => env('TRANSACTIONAL_EMAIL_TEST_MODE', true),
        'test_recipient' => env('TRANSACTIONAL_EMAIL_TEST_RECIPIENT'),
        'queue' => env('TRANSACTIONAL_EMAIL_QUEUE', 'transactional'),
        'retry_count' => (int) env('TRANSACTIONAL_EMAIL_RETRY_COUNT', 3),
        'timeout' => (int) env('TRANSACTIONAL_EMAIL_TIMEOUT', 30),
        'rate_limit_per_minute' => (int) env('TRANSACTIONAL_EMAIL_RATE_LIMIT_PER_MINUTE', 120),
    ],
    'webhooks' => [
        'secret' => env('EMAIL_WEBHOOK_SECRET'),
        'queue' => env('EMAIL_WEBHOOK_QUEUE', 'webhooks'),
        'soft_bounce_threshold' => (int) env('EMAIL_SOFT_BOUNCE_THRESHOLD', 3),
    ],
    'regional' => [
        'global' => ['name' => 'NeoGiga Global', 'base_url' => 'https://neogiga.com', 'currency' => 'USD', 'marketing_domain' => 'news.neogiga.com', 'transactional_domain' => 'mail.neogiga.com'],
        'india' => ['name' => 'NeoGiga India', 'base_url' => 'https://neogiga.in', 'currency' => 'INR', 'marketing_domain' => 'news.neogiga.in', 'transactional_domain' => 'mail.neogiga.in'],
        'nepal' => ['name' => 'Giga Nepal', 'base_url' => 'https://giganepal.com', 'currency' => 'NPR', 'marketing_domain' => 'news.giganepal.com', 'transactional_domain' => 'mail.giganepal.com'],
    ],
    'whatsapp' => ['provider' => env('WHATSAPP_PROVIDER', 'manual_export'), 'enabled' => env('WHATSAPP_ACCESS_TOKEN') && env('WHATSAPP_PHONE_NUMBER_ID')],
    'otp' => ['expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10), 'resend_cooldown' => env('OTP_RESEND_COOLDOWN', 60)],
    'analytics' => ['ga_measurement_id' => env('GA_MEASUREMENT_ID') ?: env('VITE_GA_MEASUREMENT_ID')],
];
