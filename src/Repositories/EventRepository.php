<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;

final class EventRepository
{
    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO events (workflow_id, request_id, method, content_type, headers_json, query_json, payload_json, raw_body, source_ip, received_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['workflow_id'], $data['request_id'], $data['method'], $data['content_type'],
            json_encode_safe($data['headers']), json_encode_safe($data['query']), json_encode_safe($data['payload']),
            $data['raw_body'], $data['source_ip'], now(),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT e.*, w.name workflow_name FROM events e JOIN workflows w ON w.id = e.workflow_id WHERE e.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function recent(int $limit = 50, ?int $workflowId = null): array
    {
        $limit = max(1, min(200, $limit));
        if ($workflowId) {
            $stmt = Database::connection()->prepare('SELECT e.*, w.name workflow_name FROM events e JOIN workflows w ON w.id = e.workflow_id WHERE e.workflow_id = ? ORDER BY e.id DESC LIMIT ' . $limit);
            $stmt->execute([$workflowId]);
            return $stmt->fetchAll();
        }
        return Database::connection()->query('SELECT e.*, w.name workflow_name FROM events e JOIN workflows w ON w.id = e.workflow_id ORDER BY e.id DESC LIMIT ' . $limit)->fetchAll();
    }

    public function deleteOlderThan(string $date): int
    {
        $stmt = Database::connection()->prepare('DELETE FROM events WHERE received_at < ?');
        $stmt->execute([$date]);
        return $stmt->rowCount();
    }
}
