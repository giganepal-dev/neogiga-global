<?php

return [
    'enabled' => (bool) env('CATALOG_IMPORT_ENABLED', false),
    'user_agent' => env('CATALOG_IMPORT_USER_AGENT', 'NeoGigaCatalogBot/1.0 (+catalog-admin-contact)'),
    'contact' => env('CATALOG_IMPORT_CONTACT'),
    'default_rpm' => (int) env('CATALOG_IMPORT_DEFAULT_RPM', 10),
    'max_concurrency' => (int) env('CATALOG_IMPORT_MAX_CONCURRENCY', 1),
    'media_enabled' => (bool) env('CATALOG_IMPORT_MEDIA_ENABLED', false),
    'documents_enabled' => (bool) env('CATALOG_IMPORT_DOCUMENTS_ENABLED', false),
    'timeout' => (int) env('CATALOG_IMPORT_TIMEOUT', 30),
    'connect_timeout' => (int) env('CATALOG_IMPORT_CONNECT_TIMEOUT', 10),
    'max_response_mb' => (int) env('CATALOG_IMPORT_MAX_RESPONSE_MB', 10),
    'retry_attempts' => (int) env('CATALOG_IMPORT_RETRY_ATTEMPTS', 3),
    'review_status' => env('CATALOG_IMPORT_REVIEW_STATUS', 'pending_review'),
    'suppliers' => [
        'adafruit' => [
            'name' => 'Adafruit', 'base_url' => 'https://www.adafruit.com', 'country_code' => 'US',
            'robots_url' => 'https://www.adafruit.com/robots.txt', 'terms_url' => 'https://www.adafruit.com/terms_of_service',
            'enabled' => (bool) env('ADAFRUIT_IMPORT_ENABLED', false), 'rpm' => (int) env('ADAFRUIT_IMPORT_RPM', 0),
            'media_enabled' => (bool) env('ADAFRUIT_MEDIA_ENABLED', false), 'description_reuse_status' => 'unknown',
            'sitemap_urls' => [],
        ],
        'waveshare' => [
            'name' => 'Waveshare', 'base_url' => 'https://www.waveshare.com', 'country_code' => 'CN',
            'robots_url' => 'https://www.waveshare.com/robots.txt', 'terms_url' => 'https://www.waveshare.com/wiki/Terms_of_Use',
            'alternate_domains' => ['https://www.waveshare.net'],
            'enabled' => (bool) env('WAVESHARE_IMPORT_ENABLED', false), 'rpm' => (int) env('WAVESHARE_IMPORT_RPM', 0),
            'media_enabled' => (bool) env('WAVESHARE_MEDIA_ENABLED', false), 'description_reuse_status' => 'unknown',
            'sitemap_urls' => [],
        ],
        'okystar' => [
            'name' => 'OKYSTAR', 'base_url' => 'https://www.okystar.com', 'country_code' => 'CN',
            'robots_url' => 'https://www.okystar.com/robots.txt', 'terms_url' => 'https://www.okystar.com',
            'enabled' => (bool) env('OKYSTAR_IMPORT_ENABLED', false), 'rpm' => (int) env('OKYSTAR_IMPORT_RPM', 0),
            'media_enabled' => (bool) env('OKYSTAR_MEDIA_ENABLED', false), 'description_reuse_status' => 'unknown',
            'sitemap_urls' => [],
        ],
    ],
];
