<?php

declare(strict_types=1);

namespace Patchbay;

use Patchbay\Security\Crypto;

final class Bootstrap
{
    private static bool $booted = false;

    public static function boot(bool $startSession = true): void
    {
        if (self::$booted) return;

        require_once __DIR__ . '/Support/helpers.php';
        self::registerAutoloader();
        self::loadEnv(base_path('.env'));
        self::ensureAppKey();
        Config::load(base_path('config'));

        date_default_timezone_set((string) config('app.timezone', 'UTC'));
        self::ensureStorage();

        if ($startSession && PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            session_name((string) config('app.session_name', 'patchbay_session'));
            session_set_cookie_params([
                'httponly' => true,
                'secure' => (bool) config('app.session_secure', false),
                'samesite' => 'Lax',
                'path' => '/',
            ]);
            session_start();
        }

        if ((string) config('app.key', '') === '') {
            error_log('Patchbay warning: APP_KEY is empty. Set a strong random key before production use.');
        } else {
            Crypto::assertKeyIsUsable((string) config('app.key'));
        }

        self::$booted = true;
    }

    private static function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'Patchbay\\';
            if (!str_starts_with($class, $prefix)) return;
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) require_once $file;
        });
    }

    private static function loadEnv(string $path): void
    {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (($value[0] ?? '') === '"' && str_ends_with($value, '"')) $value = substr($value, 1, -1);
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv($name . '=' . $value);
            }
        }
    }

    private static function ensureAppKey(): void
    {
        if ((string) env('APP_KEY', '') !== '') return;
        $storage = base_path('storage');
        if (!is_dir($storage)) mkdir($storage, 0775, true);
        $file = $storage . DIRECTORY_SEPARATOR . 'app.key';
        $key = is_file($file) ? trim((string) file_get_contents($file)) : '';
        if (strlen($key) < 24) {
            $key = bin2hex(random_bytes(32));
            if (file_put_contents($file, $key, LOCK_EX) === false) {
                throw new \RuntimeException('Unable to create storage/app.key. Set APP_KEY manually.');
            }
            @chmod($file, 0600);
        }
        $_ENV['APP_KEY'] = $key;
        putenv('APP_KEY=' . $key);
    }

    private static function ensureStorage(): void
    {
        foreach (['storage/database', 'storage/logs', 'storage/cache'] as $dir) {
            $path = base_path($dir);
            if (!is_dir($path)) mkdir($path, 0775, true);
        }
    }
}
