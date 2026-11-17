<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;

final class AuditRepository
{
    public function write(?int $userId, string $action, string $entityType, ?int $entityId = null, array $context = []): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, context_json, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $action, $entityType, $entityId, json_encode_safe($context), now()]);
    }

    public function recent(int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        return Database::connection()->query('SELECT a.*, u.name user_name FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT ' . $limit)->fetchAll();
    }
}
