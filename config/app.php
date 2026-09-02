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
];
