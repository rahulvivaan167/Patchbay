<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;
use Patchbay\Security\Crypto;

final class SecretRepository
{
    public function all(): array
    {
        return Database::connection()->query('SELECT id, name, created_at, updated_at FROM secrets ORDER BY name ASC')->fetchAll();
    }

    public function values(): array
    {
        $rows = Database::connection()->query('SELECT name, value_enc FROM secrets')->fetchAll();
        $out = [];
        foreach ($rows as $row) $out[$row['name']] = Crypto::decrypt((string) $row['value_enc']);
        return $out;
    }

    public function upsert(string $name, string $value): int
    {
        $pdo = Database::connection();
        $name = strtoupper(trim($name));
        $encrypted = Crypto::encrypt($value);
        $find = $pdo->prepare('SELECT id FROM secrets WHERE name = ?');
        $find->execute([$name]);
        $id = $find->fetchColumn();
        if ($id !== false) {
            $pdo->prepare('UPDATE secrets SET value_enc = ?, updated_at = ? WHERE id = ?')->execute([$encrypted, now(), $id]);
            return (int) $id;
        }
        $pdo->prepare('INSERT INTO secrets (name, value_enc, created_at, updated_at) VALUES (?, ?, ?, ?)')->execute([$name, $encrypted, now(), now()]);
        return (int) $pdo->lastInsertId();
    }

    public function delete(int $id): void
    {
        Database::connection()->prepare('DELETE FROM secrets WHERE id = ?')->execute([$id]);
    }
}
