<?php

declare(strict_types=1);

return [
    'env' => env('APP_ENV', 'production'),
    'url' => env('APP_URL', 'http://localhost:8088'),
    'db' => [
        'host' => env('DB_HOST', 'db'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'ordena'),
        'username' => env('DB_USERNAME', 'ordena'),
        'password' => env('DB_PASSWORD', ''),
    ],
    'geocoding' => [
        'user_agent' => env('GEOCODING_USER_AGENT', ''),
        'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
        'base_url' => env('GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'timeout' => (int) env('GEOCODING_TIMEOUT', '10'),
    ],
    'redis' => [
        'host' => env('REDIS_HOST', 'redis'),
        'port' => (int) env('REDIS_PORT', '6379'),
    ],
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'log'),
        'twilio' => [
            'sid' => env('TWILIO_SID', ''),
            'token' => env('TWILIO_TOKEN', ''),
            'from' => env('TWILIO_FROM', ''),
        ],
    ],
    'otp' => [
        'ttl_minutes' => (int) env('OTP_TTL_MINUTES', '10'),
        'max_attempts' => (int) env('OTP_MAX_ATTEMPTS', '5'),
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', '60'),
    ],
];
