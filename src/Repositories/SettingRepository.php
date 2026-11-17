<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;

final class SettingRepository
{
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = Database::connection()->prepare('SELECT value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? $default : (string) $value;
    }

    public function set(string $key, string $value): void
    {
        $pdo = Database::connection();
        $existing = $pdo->prepare('SELECT 1 FROM settings WHERE setting_key = ?');
        $existing->execute([$key]);
        if ($existing->fetchColumn()) {
            $pdo->prepare('UPDATE settings SET value = ?, updated_at = ? WHERE setting_key = ?')->execute([$value, now(), $key]);
        } else {
            $pdo->prepare('INSERT INTO settings (setting_key, value, updated_at) VALUES (?, ?, ?)')->execute([$key, $value, now()]);
        }
    }

    public function all(): array
    {
        $rows = Database::connection()->query('SELECT setting_key, value FROM settings')->fetchAll();
        return array_column($rows, 'value', 'setting_key');
    }
}
