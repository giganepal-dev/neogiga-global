<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Marketplace
    |--------------------------------------------------------------------------
    */
    'default' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Base Currency
    |--------------------------------------------------------------------------
    */
    'base_currency' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Supported Countries (ISO 3166-1 alpha-2)
    |--------------------------------------------------------------------------
    */
    'countries' => [
        // South Asia
        'NP' => ['name' => 'Nepal', 'subdomain' => 'np', 'currency' => 'NPR', 'timezone' => 'Asia/Kathmandu'],
        'IN' => ['name' => 'India', 'subdomain' => 'in', 'currency' => 'INR', 'timezone' => 'Asia/Kolkata'],
        'BD' => ['name' => 'Bangladesh', 'subdomain' => 'bd', 'currency' => 'BDT', 'timezone' => 'Asia/Dhaka'],
        'LK' => ['name' => 'Sri Lanka', 'subdomain' => 'lk', 'currency' => 'LKR', 'timezone' => 'Asia/Colombo'],
        'PK' => ['name' => 'Pakistan', 'subdomain' => 'pk', 'currency' => 'PKR', 'timezone' => 'Asia/Karachi'],
        'BT' => ['name' => 'Bhutan', 'subdomain' => 'bt', 'currency' => 'BTN', 'timezone' => 'Asia/Thimphu'],
        'MV' => ['name' => 'Maldives', 'subdomain' => 'mv', 'currency' => 'MVR', 'timezone' => 'Indian/Maldives'],
        
        // Middle East
        'AE' => ['name' => 'UAE', 'subdomain' => 'ae', 'currency' => 'AED', 'timezone' => 'Asia/Dubai'],
        'QA' => ['name' => 'Qatar', 'subdomain' => 'qa', 'currency' => 'QAR', 'timezone' => 'Asia/Qatar'],
        'SA' => ['name' => 'Saudi Arabia', 'subdomain' => 'sa', 'currency' => 'SAR', 'timezone' => 'Asia/Riyadh'],
        
        // Oceania
        'AU' => ['name' => 'Australia', 'subdomain' => 'au', 'currency' => 'AUD', 'timezone' => 'Australia/Sydney'],
        
        // North America
        'CA' => ['name' => 'Canada', 'subdomain' => 'ca', 'currency' => 'CAD', 'timezone' => 'America/Toronto'],
        'US' => ['name' => 'United States', 'subdomain' => 'us', 'currency' => 'USD', 'timezone' => 'America/New_York'],
        'MX' => ['name' => 'Mexico', 'subdomain' => 'mx', 'currency' => 'MXN', 'timezone' => 'America/Mexico_City'],
        
        // Europe
        'UK' => ['name' => 'United Kingdom', 'subdomain' => 'uk', 'currency' => 'GBP', 'timezone' => 'Europe/London'],
        'DE' => ['name' => 'Germany', 'subdomain' => 'de', 'currency' => 'EUR', 'timezone' => 'Europe/Berlin'],
        'FR' => ['name' => 'France', 'subdomain' => 'fr', 'currency' => 'EUR', 'timezone' => 'Europe/Paris'],
        'IT' => ['name' => 'Italy', 'subdomain' => 'it', 'currency' => 'EUR', 'timezone' => 'Europe/Rome'],
        'ES' => ['name' => 'Spain', 'subdomain' => 'es', 'currency' => 'EUR', 'timezone' => 'Europe/Madrid'],
        
        // Asia Pacific
        'SG' => ['name' => 'Singapore', 'subdomain' => 'sg', 'currency' => 'SGD', 'timezone' => 'Asia/Singapore'],
        'MY' => ['name' => 'Malaysia', 'subdomain' => 'my', 'currency' => 'MYR', 'timezone' => 'Asia/Kuala_Lumpur'],
        'ID' => ['name' => 'Indonesia', 'subdomain' => 'id', 'currency' => 'IDR', 'timezone' => 'Asia/Jakarta'],
        'TH' => ['name' => 'Thailand', 'subdomain' => 'th', 'currency' => 'THB', 'timezone' => 'Asia/Bangkok'],
        'VN' => ['name' => 'Vietnam', 'subdomain' => 'vn', 'currency' => 'VND', 'timezone' => 'Asia/Ho_Chi_Minh'],
        'PH' => ['name' => 'Philippines', 'subdomain' => 'ph', 'currency' => 'PHP', 'timezone' => 'Asia/Manila'],
        'JP' => ['name' => 'Japan', 'subdomain' => 'jp', 'currency' => 'JPY', 'timezone' => 'Asia/Tokyo'],
        'KR' => ['name' => 'South Korea', 'subdomain' => 'kr', 'currency' => 'KRW', 'timezone' => 'Asia/Seoul'],
        
        // South America
        'BR' => ['name' => 'Brazil', 'subdomain' => 'br', 'currency' => 'BRL', 'timezone' => 'America/Sao_Paulo'],
        
        // Africa
        'ZA' => ['name' => 'South Africa', 'subdomain' => 'za', 'currency' => 'ZAR', 'timezone' => 'Africa/Johannesburg'],
        'KE' => ['name' => 'Kenya', 'subdomain' => 'ke', 'currency' => 'KES', 'timezone' => 'Africa/Nairobi'],
        'NG' => ['name' => 'Nigeria', 'subdomain' => 'ng', 'currency' => 'NGN', 'timezone' => 'Africa/Lagos'],
        'EG' => ['name' => 'Egypt', 'subdomain' => 'eg', 'currency' => 'EGP', 'timezone' => 'Africa/Cairo'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways by Country
    |--------------------------------------------------------------------------
    */
    'payment_gateways' => [
        'NP' => ['esewa', 'khalti', 'fonepay', 'ime_pay', 'connectips', 'cod'],
        'IN' => ['razorpay', 'payu', 'cashfree', 'phonepe', 'upi', 'cod'],
        'BD' => ['bkash', 'nagad', 'rocket', 'cod'],
        'LK' => ['genie', 'frimi', 'cod'],
        'AU' => ['stripe', 'paypal'],
        'CA' => ['stripe', 'moneris', 'paypal'],
        'US' => ['stripe', 'paypal', 'authorize_net'],
        'UK' => ['stripe', 'paypal'],
        'DE' => ['stripe', 'paypal', 'sofort'],
        'FR' => ['stripe', 'paypal'],
        'default' => ['stripe', 'paypal', 'bank_transfer'],
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP Settings
    |--------------------------------------------------------------------------
    */
    'geoip' => [
        'enabled' => true,
        'database_path' => storage_path('app/geo/GeoLite2-Country.mmdb'),
        'fallback_country' => 'US',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache_ttl' => [
        'marketplace' => 3600,
        'pricing' => 300,
        'inventory' => 60,
        'exchange_rates' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Rounding Rules
    |--------------------------------------------------------------------------
    */
    'price_rounding' => [
        'default' => 'nearest_09',
        'options' => ['nearest_00', 'nearest_05', 'nearest_09', 'nearest_99'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Defaults
    |--------------------------------------------------------------------------
    */
    'seo' => [
        'title_suffix' => '| NeoGiga',
        'description_prefix' => 'Buy electronic components, development boards, and PCB services at NeoGiga.',
        'keywords' => ['electronic components', 'semiconductors', 'development boards', 'PCB fabrication', 'IoT', 'sensors'],
    ],
];
