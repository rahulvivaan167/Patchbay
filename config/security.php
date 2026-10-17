<?php

declare(strict_types=1);

return [
    'webhook_rate_limit' => env_int('RATE_LIMIT_WEBHOOKS', 120),
    'api_rate_limit' => env_int('RATE_LIMIT_API', 240),
    'csrf_ttl' => 7200,
    'login_rate_limit' => 10,
    'login_window_seconds' => 900,
];
