<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;
use Patchbay\Security\Token;

final class ApiKeyRepository
{
    public function all(): array
    {
        return Database::connection()->query('SELECT id, name, key_prefix, last_used_at, created_at, revoked_at FROM api_keys ORDER BY id DESC')->fetchAll();
    }

    public function create(string $name): array
    {
        $key = Token::apiKey();
        $stmt = Database::connection()->prepare('INSERT INTO api_keys (name, key_prefix, key_hash, created_at) VALUES (?, ?, ?, ?)');
        $stmt->execute([trim($name), $key['prefix'], $key['hash'], now()]);
        return ['id' => (int) Database::connection()->lastInsertId(), ...$key];
    }

    public function authenticate(string $plain): ?array
    {
        $hash = hash('sha256', $plain);
        $stmt = Database::connection()->prepare('SELECT * FROM api_keys WHERE key_hash = ? AND revoked_at IS NULL');
        $stmt->execute([$hash]);
        $key = $stmt->fetch();
        if (!$key) return null;
        Database::connection()->prepare('UPDATE api_keys SET last_used_at = ? WHERE id = ?')->execute([now(), $key['id']]);
        return $key;
    }

    public function revoke(int $id): void
    {
        Database::connection()->prepare('UPDATE api_keys SET revoked_at = ? WHERE id = ?')->execute([now(), $id]);
    }
}
