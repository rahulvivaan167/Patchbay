<?php

declare(strict_types=1);

namespace Patchbay\Auth;

use Patchbay\Http\Response;
use Patchbay\Repositories\UserRepository;

final class Auth
{
    private static ?array $user = null;

    public static function attempt(string $email, string $password): bool
    {
        $user = (new UserRepository())->findByEmail(strtolower(trim($email)));
        if (!$user || !password_verify($password, $user['password_hash'])) return false;
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        (new UserRepository())->touchLogin((int) $user['id']);
        self::$user = $user;
        return true;
    }

    public static function user(): ?array
    {
        if (self::$user) return self::$user;
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) return null;
        return self::$user = (new UserRepository())->find($id);
    }

    public static function check(): bool { return self::user() !== null; }

    public static function requireLogin(): void
    {
        if (!self::check()) Response::redirect('/login');
    }

    public static function logout(): void
    {
        self::$user = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
