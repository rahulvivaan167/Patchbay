<?php

declare(strict_types=1);

namespace Patchbay\Security;

final class Token
{
    public static function random(int $bytes = 24): string
    {
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public static function apiKey(): array
    {
        $secret = 'pb_' . self::random(30);
        return [
            'plain' => $secret,
            'prefix' => substr($secret, 0, 12),
            'hash' => hash('sha256', $secret),
        ];
    }
}
