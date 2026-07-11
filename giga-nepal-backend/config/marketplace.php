<?php

return [
    /*
     | Host-spoofing allow-list guard (codex §6). OFF by default. Enabling it
     | rejects any request whose Host header is not in the data-driven allow-list
     | (built from marketplace domains + the hosts below), so it must ONLY be
     | turned on after the allow-list has been verified against every live host.
     | The guard is fail-open: any internal error lets the request through.
     */
    'host_guard_enabled' => (bool) env('MARKETPLACE_HOST_GUARD', false),

    // Extra always-allowed hosts, comma-separated (CDN, health-check host, ...).
    'allowed_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MARKETPLACE_ALLOWED_HOSTS', ''))
    ))),

    // Always allowed regardless of the flag (local dev / loopback).
    'always_allow' => ['localhost', '127.0.0.1', '::1'],
];
