<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Restricted to the NeoGiga production origins. The API is served from
    | backend.neogiga.com; browser clients on the public and admin hosts must
    | be explicitly allowed. No wildcard, no credentials (bearer-token auth).
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => [
        'https://neogiga.com',
        'https://www.neogiga.com',
        'https://admin.neogiga.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-Admin-Token'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,

];
