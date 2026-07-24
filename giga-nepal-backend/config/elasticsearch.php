<?php

return [
    'hosts' => [
        env('ELASTICSEARCH_HOST', 'https://localhost:9200'),
    ],
    'auth' => [
        'username' => env('ELASTICSEARCH_USERNAME', 'elastic'),
        'password' => env('ELASTICSEARCH_PASSWORD', ''),
    ],
    'ssl_verification' => env('ELASTICSEARCH_SSL_VERIFICATION', false),
    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'neogiga'),
    'rebuild_chunk_size' => (int) env('ELASTICSEARCH_REBUILD_CHUNK', 500),
];
