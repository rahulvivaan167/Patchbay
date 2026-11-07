<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\EventRepository;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Repositories\WorkflowRepository;
use Patchbay\Services\ApiAuthenticator;

final class ApiController
{
    private function auth(Request $request): void { (new ApiAuthenticator())->require($request); }

    public function workflows(Request $request): never
    {
        $this->auth($request);
        $rows = array_map(static function (array $w): array {
            return ['id' => (int) $w['id'], 'name' => $w['name'], 'slug' => $w['slug'], 'active' => (bool) $w['is_active'], 'updated_at' => $w['updated_at']];
        }, (new WorkflowRepository())->all());
        Response::json(['data' => $rows]);
    }

    public function events(Request $request): never
    {
        $this->auth($request);
        $rows = array_map(static fn(array $e): array => [
            'id' => (int) $e['id'], 'request_id' => $e['request_id'], 'workflow_id' => (int) $e['workflow_id'], 'workflow' => $e['workflow_name'], 'method' => $e['method'], 'source_ip' => $e['source_ip'], 'received_at' => $e['received_at'],
        ], (new EventRepository())->recent(100));
        Response::json(['data' => $rows]);
    }

    public function executions(Request $request): never
    {
        $this->auth($request);
        $status = trim((string) $request->input('status', ''));
        $rows = array_map(static fn(array $x): array => [
            'id' => (int) $x['id'], 'request_id' => $x['request_id'], 'workflow_id' => (int) $x['workflow_id'], 'workflow' => $x['workflow_name'], 'status' => $x['status'], 'attempts' => (int) $x['attempts'], 'created_at' => $x['created_at'], 'finished_at' => $x['finished_at'],
        ], (new ExecutionRepository())->recent(100, $status ?: null));
        Response::json(['data' => $rows]);
    }

    public function execution(Request $request, string $id): never
    {
        $this->auth($request);
        $repo = new ExecutionRepository();
        $row = $repo->find((int) $id);
        if (!$row) Response::json(['error' => 'not_found'], 404);
        Response::json(['data' => $row, 'logs' => $repo->logs((int) $id)]);
    }
}
