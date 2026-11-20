<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;
use Patchbay\Security\Token;

final class WorkflowRepository
{
    public function all(): array
    {
        return Database::connection()->query('SELECT * FROM workflows ORDER BY updated_at DESC, id DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM workflows WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByToken(string $token): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM workflows WHERE endpoint_token = ? AND is_active = 1');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $token = Token::random(24);
        $slug = $this->uniqueSlug($data['name'] ?? 'workflow');
        $stmt = Database::connection()->prepare(
            'INSERT INTO workflows (name, slug, description, endpoint_token, is_active, definition_json, allowed_origins_json, require_signature, signing_secret_enc, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            trim((string) $data['name']),
            $slug,
            trim((string) ($data['description'] ?? '')),
            $token,
            !empty($data['is_active']) ? 1 : 0,
            (string) $data['definition_json'],
            json_encode_safe($data['allowed_origins'] ?? []),
            !empty($data['require_signature']) ? 1 : 0,
            (string) ($data['signing_secret_enc'] ?? ''),
            now(),
            now(),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE workflows SET name = ?, description = ?, is_active = ?, definition_json = ?, allowed_origins_json = ?, require_signature = ?, signing_secret_enc = ?, updated_at = ? WHERE id = ?'
        );
        $stmt->execute([
            trim((string) $data['name']),
            trim((string) ($data['description'] ?? '')),
            !empty($data['is_active']) ? 1 : 0,
            (string) $data['definition_json'],
            json_encode_safe($data['allowed_origins'] ?? []),
            !empty($data['require_signature']) ? 1 : 0,
            (string) ($data['signing_secret_enc'] ?? ''),
            now(),
            $id,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM workflows WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function rotateToken(int $id): string
    {
        $token = Token::random(24);
        $stmt = Database::connection()->prepare('UPDATE workflows SET endpoint_token = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$token, now(), $id]);
        return $token;
    }

    public function stats(int $id): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT COUNT(*) total, SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) successful, SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) failed FROM executions WHERE workflow_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch() ?: [];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'successful' => (int) ($row['successful'] ?? 0),
            'failed' => (int) ($row['failed'] ?? 0),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'workflow';
        $slug = $base;
        $i = 2;
        while ($this->slugExists($slug)) $slug = $base . '-' . $i++;
        return $slug;
    }

    private function slugExists(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT 1 FROM workflows WHERE slug = ?');
        $stmt->execute([$slug]);
        return (bool) $stmt->fetchColumn();
    }
}
