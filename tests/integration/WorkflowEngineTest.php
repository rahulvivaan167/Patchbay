<?php

declare(strict_types=1);

use Patchbay\Database;
use Patchbay\Repositories\EventRepository;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Repositories\WorkflowRepository;
use Patchbay\Services\WorkflowEngine;

return function (): void {
    $workflowId = (new WorkflowRepository())->create([
        'name' => 'Integration flow',
        'description' => 'Test',
        'is_active' => true,
        'definition_json' => json_encode_safe(['steps' => [
            ['type' => 'filter', 'field' => 'event.type', 'operator' => 'equals', 'value' => 'push'],
            ['type' => 'transform', 'mode' => 'map', 'mapping' => ['repo' => 'repository.full_name', 'source' => '=patchbay']],
            ['type' => 'log', 'message' => 'Processed {{event.request_id}}'],
        ]]),
        'allowed_origins' => [],
        'require_signature' => false,
        'signing_secret_enc' => '',
    ]);

    $eventId = (new EventRepository())->create([
        'workflow_id' => $workflowId,
        'request_id' => 'evt_integration_1',
        'method' => 'POST',
        'content_type' => 'application/json',
        'headers' => ['x-test' => '1'],
        'query' => [],
        'payload' => ['event' => ['type' => 'push'], 'repository' => ['full_name' => 'rahul/Patchbay']],
        'raw_body' => '{}',
        'source_ip' => '203.0.113.10',
    ]);

    $executionId = (new ExecutionRepository())->create($workflowId, $eventId);
    (new WorkflowEngine())->process($executionId);
    $execution = (new ExecutionRepository())->find($executionId);
    assert_same('success', $execution['status']);
    assert_same(3, count((new ExecutionRepository())->logs($executionId)));
};
