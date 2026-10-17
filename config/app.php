<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Patchbay'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env_bool('APP_DEBUG', false),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8080'), '/'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'key' => env('APP_KEY', ''),
    'session_name' => env('SESSION_NAME', 'patchbay_session'),
    'session_secure' => env_bool('SESSION_SECURE', false),
    'trust_proxy' => env_bool('TRUST_PROXY', false),
    'max_payload_bytes' => env_int('MAX_PAYLOAD_BYTES', 1048576),
    'http_timeout' => env_int('HTTP_TIMEOUT', 15),
    'http_user_agent' => env('HTTP_USER_AGENT', 'Patchbay/1.0'),
    'retention_days' => env_int('RETENTION_DAYS', 30),
    'worker_sleep' => env_int('WORKER_SLEEP', 3),
];
