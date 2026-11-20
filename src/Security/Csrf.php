<?php

declare(strict_types=1);

namespace Patchbay\Security;

use Patchbay\Http\Request;

final class Csrf
{
    public static function token(): string
    {
        $ttl = (int) config('security.csrf_ttl', 7200);
        if (!isset($_SESSION['_csrf_token'], $_SESSION['_csrf_issued']) || time() - (int) $_SESSION['_csrf_issued'] > $ttl) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['_csrf_issued'] = time();
        }
        return (string) $_SESSION['_csrf_token'];
    }

    public static function verify(Request $request): bool
    {
        $provided = (string) ($request->post['_token'] ?? $request->header('X-CSRF-Token', ''));
        $stored = (string) ($_SESSION['_csrf_token'] ?? '');
        return $provided !== '' && $stored !== '' && hash_equals($stored, $provided);
    }
}
