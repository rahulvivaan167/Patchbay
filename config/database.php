<?php

declare(strict_types=1);

return [
    'driver' => env('DB_DRIVER', 'sqlite'),
    'sqlite' => [
        'database' => env('DB_DATABASE', 'storage/database/patchbay.sqlite'),
    ],
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env_int('DB_PORT', 3306),
        'database' => env('DB_NAME', 'patchbay'),
        'username' => env('DB_USER', 'patchbay'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
];
