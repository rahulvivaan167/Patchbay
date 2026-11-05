<?php

declare(strict_types=1);

namespace Patchbay;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private const SCHEMA_VERSION = '1';
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) return self::$pdo;
        $driver = (string) config('database.driver', 'sqlite');
        try {
            if ($driver === 'sqlite') {
                $path = (string) config('database.sqlite.database');
                if (!str_starts_with($path, DIRECTORY_SEPARATOR)) $path = base_path($path);
                $dir = dirname($path);
                if (!is_dir($dir)) mkdir($dir, 0775, true);
                self::$pdo = new PDO('sqlite:' . $path);
                self::$pdo->exec('PRAGMA foreign_keys = ON');
                self::$pdo->exec('PRAGMA journal_mode = WAL');
                self::$pdo->exec('PRAGMA busy_timeout = 5000');
            } elseif ($driver === 'mysql') {
                $cfg = config('database.mysql', []);
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
                self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password']);
            } else {
                throw new RuntimeException('Unsupported DB_DRIVER: ' . $driver);
            }
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            return self::$pdo;
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public static function ensureSchema(): void
    {
        try {
            $stmt = self::connection()->prepare('SELECT value FROM settings WHERE setting_key = ?');
            $stmt->execute(['schema_version']);
            if ((string) $stmt->fetchColumn() === self::SCHEMA_VERSION) return;
        } catch (\Throwable) {
            // First boot: the settings table does not exist yet.
        }
        self::migrate();
    }

    public static function migrate(): void
    {
        $driver = (string) config('database.driver', 'sqlite');
        $file = base_path('database/schema.' . $driver . '.sql');
        if (!is_file($file)) throw new RuntimeException('Missing schema file for driver: ' . $driver);
        $sql = (string) file_get_contents($file);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        $pdo = self::connection();
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') $pdo->exec($statement);
        }
        if ($driver === 'mysql') {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value, updated_at) VALUES ('schema_version', ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = VALUES(updated_at)");
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, value, updated_at) VALUES ('schema_version', ?, ?) ON CONFLICT(setting_key) DO UPDATE SET value = excluded.value, updated_at = excluded.updated_at");
        }
        $stmt->execute([self::SCHEMA_VERSION, now()]);
    }

    public static function resetForTests(): void
    {
        self::$pdo = null;
    }
}
