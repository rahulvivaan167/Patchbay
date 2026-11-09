<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Services\AuditService;

final class ExecutionController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $status = trim((string) $request->input('status', ''));
        $this->view('executions.index', ['title' => 'Executions', 'executions' => (new ExecutionRepository())->recent(100, $status ?: null), 'status' => $status]);
    }

    public function show(Request $request, string $id): never
    {
        $this->auth();
        $repo = new ExecutionRepository();
        $execution = $repo->find((int) $id);
        if (!$execution) Response::html('<h1>404</h1>', 404);
        $this->view('executions.show', ['title' => 'Execution #' . $id, 'execution' => $execution, 'logs' => $repo->logs((int) $id)]);
    }

    public function retry(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        (new ExecutionRepository())->retry((int) $id);
        (new AuditService())->record('retried', 'execution', (int) $id);
        $this->flash('success', 'Execution queued again from the first step.');
        Response::redirect('/executions/' . $id);
    }
}
