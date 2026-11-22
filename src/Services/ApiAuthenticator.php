<?php

declare(strict_types=1);

namespace Patchbay\Services;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\ApiKeyRepository;
use Patchbay\Security\RateLimiter;

final class ApiAuthenticator
{
    public function require(Request $request): array
    {
        $limit = (int) config('security.api_rate_limit', 240);
        if (!(new RateLimiter())->hit('api:' . $request->ip(), $limit, 60)) {
            Response::json(['error' => 'rate_limited'], 429);
        }
        $token = $request->bearerToken();
        if (!$token) Response::json(['error' => 'missing_api_key'], 401);
        $key = (new ApiKeyRepository())->authenticate($token);
        if (!$key) Response::json(['error' => 'invalid_api_key'], 401);
        return $key;
    }
}
