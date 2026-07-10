<?php

return [
    // All central pricing math is anchored to this currency; exchange_rates
    // rows are recorded FROM this currency TO each marketplace currency.
    'base_currency' => env('PRICING_BASE_CURRENCY', 'USD'),

    // A rate older than this is refused by freshRate() — calculations fail
    // loudly instead of silently using a stale rate.
    'rate_staleness_hours' => (int) env('PRICING_RATE_STALENESS_HOURS', 48),

    // Rates for the manual provider, e.g. ['NPR' => 133.0, 'INR' => 83.5].
    // Empty by default: real rates are an operational decision entered by an
    // operator (env/config on the server), never invented in code.
    'manual_rates' => [],

    // Default margin percent when a marketplace has no
    // marketplace_settings row for pricing.margin_percent.
    'default_margin_percent' => (float) env('PRICING_DEFAULT_MARGIN_PERCENT', 0),
];
