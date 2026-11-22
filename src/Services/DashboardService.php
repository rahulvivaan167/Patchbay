<?php

declare(strict_types=1);

namespace Patchbay\Services;

use Patchbay\Database;

final class DashboardService
{
    public function metrics(): array
    {
        $pdo = Database::connection();
        $workflowCount = (int) $pdo->query('SELECT COUNT(*) FROM workflows')->fetchColumn();
        $activeCount = (int) $pdo->query('SELECT COUNT(*) FROM workflows WHERE is_active = 1')->fetchColumn();
        $cutoff = (new \DateTimeImmutable('now'))->modify('-1 day')->format('Y-m-d H:i:s');
        $eventsStmt = $pdo->prepare('SELECT COUNT(*) FROM events WHERE received_at >= ?');
        $eventsStmt->execute([$cutoff]);
        $events24 = (int) $eventsStmt->fetchColumn();
        $statuses = ['queued' => 0, 'running' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($pdo->query('SELECT status, COUNT(*) count FROM executions GROUP BY status')->fetchAll() as $row) {
            $statuses[$row['status']] = (int) $row['count'];
        }
        $recent = $pdo->query('SELECT x.*, w.name workflow_name, e.request_id FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id ORDER BY x.id DESC LIMIT 8')->fetchAll();
        return compact('workflowCount', 'activeCount', 'events24', 'statuses', 'recent');
    }
}
