<?php

return [
    'email' => ['provider' => env('MARKETING_EMAIL_PROVIDER', 'log'), 'test_mode' => env('MARKETING_EMAIL_TEST_MODE', true)],
    'whatsapp' => ['provider' => env('WHATSAPP_PROVIDER', 'manual_export'), 'enabled' => env('WHATSAPP_ACCESS_TOKEN') && env('WHATSAPP_PHONE_NUMBER_ID')],
    'otp' => ['expiry_minutes' => env('OTP_EXPIRY_MINUTES', 10), 'resend_cooldown' => env('OTP_RESEND_COOLDOWN', 60)],
    'analytics' => ['ga_measurement_id' => env('GA_MEASUREMENT_ID') ?: env('VITE_GA_MEASUREMENT_ID')],
];
