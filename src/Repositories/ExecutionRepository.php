<?php

declare(strict_types=1);

namespace Patchbay\Repositories;

use Patchbay\Database;

final class ExecutionRepository
{
    public function create(int $workflowId, int $eventId): int
    {
        $stmt = Database::connection()->prepare("INSERT INTO executions (workflow_id, event_id, status, current_step, attempts, created_at, next_run_at) VALUES (?, ?, 'queued', 0, 0, ?, ?)");
        $stmt->execute([$workflowId, $eventId, now(), now()]);
        return (int) Database::connection()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT x.*, w.name workflow_name, e.request_id FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id WHERE x.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function context(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT x.*, w.name workflow_name, w.definition_json, w.endpoint_token, e.payload_json, e.headers_json, e.query_json, e.request_id, e.raw_body FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id WHERE x.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function logs(int $id): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM execution_logs WHERE execution_id = ? ORDER BY id ASC');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function recentForWorkflow(int $workflowId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = Database::connection()->prepare('SELECT x.*, w.name workflow_name, e.request_id FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id WHERE x.workflow_id = ? ORDER BY x.id DESC LIMIT ' . $limit);
        $stmt->execute([$workflowId]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 50, ?string $status = null): array
    {
        $limit = max(1, min(200, $limit));
        if ($status && in_array($status, ['queued', 'running', 'success', 'failed', 'skipped'], true)) {
            $stmt = Database::connection()->prepare('SELECT x.*, w.name workflow_name, e.request_id FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id WHERE x.status = ? ORDER BY x.id DESC LIMIT ' . $limit);
            $stmt->execute([$status]);
            return $stmt->fetchAll();
        }
        return Database::connection()->query('SELECT x.*, w.name workflow_name, e.request_id FROM executions x JOIN workflows w ON w.id = x.workflow_id JOIN events e ON e.id = x.event_id ORDER BY x.id DESC LIMIT ' . $limit)->fetchAll();
    }

    public function due(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = Database::connection()->prepare("SELECT id FROM executions WHERE status = 'queued' AND next_run_at <= ? ORDER BY next_run_at ASC, id ASC LIMIT {$limit}");
        $stmt->execute([now()]);
        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    public function claim(int $id): bool
    {
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'running', attempts = attempts + 1, claimed_at = ?, started_at = COALESCE(started_at, ?), error_message = NULL WHERE id = ? AND status = 'queued'");
        $stamp = now();
        $stmt->execute([$stamp, $stamp, $id]);
        return $stmt->rowCount() === 1;
    }

    public function saveProgress(int $id, int $nextStep, array $payload): void
    {
        $stmt = Database::connection()->prepare('UPDATE executions SET current_step = ?, payload_state_json = ? WHERE id = ?');
        $stmt->execute([$nextStep, json_encode_safe($payload), $id]);
    }

    public function failedAttemptsForStep(int $id, int $stepIndex): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM execution_logs WHERE execution_id = ? AND step_index = ? AND step_type = 'http' AND status = 'failed'");
        $stmt->execute([$id, $stepIndex]);
        return (int) $stmt->fetchColumn();
    }

    public function markSuccess(int $id, int $step): void
    {
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'success', current_step = ?, claimed_at = NULL, finished_at = ?, next_run_at = NULL, error_message = NULL WHERE id = ?");
        $stmt->execute([$step, now(), $id]);
    }

    public function markSkipped(int $id, int $step, string $reason): void
    {
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'skipped', current_step = ?, claimed_at = NULL, finished_at = ?, next_run_at = NULL, error_message = ? WHERE id = ?");
        $stmt->execute([$step, now(), $reason, $id]);
    }

    public function markFailed(int $id, int $step, string $error): void
    {
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'failed', current_step = ?, claimed_at = NULL, finished_at = ?, next_run_at = NULL, error_message = ? WHERE id = ?");
        $stmt->execute([$step, now(), substr($error, 0, 2000), $id]);
    }

    public function reschedule(int $id, int $step, int $delaySeconds, string $reason = '', ?array $payload = null): void
    {
        $next = (new \DateTimeImmutable('now'))->modify('+' . max(1, $delaySeconds) . ' seconds')->format('Y-m-d H:i:s');
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'queued', current_step = ?, payload_state_json = COALESCE(?, payload_state_json), claimed_at = NULL, next_run_at = ?, error_message = ? WHERE id = ?");
        $stmt->execute([$step, $payload === null ? null : json_encode_safe($payload), $next, $reason ?: null, $id]);
    }

    public function recoverStale(int $seconds = 300): int
    {
        $cutoff = (new \DateTimeImmutable('now'))->modify('-' . max(60, $seconds) . ' seconds')->format('Y-m-d H:i:s');
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'queued', claimed_at = NULL, next_run_at = ?, error_message = 'Recovered after worker interruption.' WHERE status = 'running' AND claimed_at IS NOT NULL AND claimed_at < ?");
        $stmt->execute([now(), $cutoff]);
        return $stmt->rowCount();
    }

    public function retry(int $id): void
    {
        $stmt = Database::connection()->prepare("UPDATE executions SET status = 'queued', current_step = 0, payload_state_json = NULL, attempts = 0, claimed_at = NULL, started_at = NULL, finished_at = NULL, next_run_at = ?, error_message = NULL WHERE id = ?");
        $stmt->execute([now(), $id]);
    }

    public function addLog(int $executionId, int $stepIndex, string $stepType, string $status, array $input = [], array $output = [], ?int $httpStatus = null, int $durationMs = 0, ?string $error = null): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO execution_logs (execution_id, step_index, step_type, status, input_json, output_json, http_status, duration_ms, error_message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$executionId, $stepIndex, $stepType, $status, json_encode_safe($input), json_encode_safe($output), $httpStatus, $durationMs, $error ? substr($error, 0, 2000) : null, now()]);
    }
}
