<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Domain\WorkflowDefinition;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Repositories\WorkflowRepository;
use Patchbay\Security\Crypto;
use Patchbay\Security\Token;
use Patchbay\Services\AuditService;
use Patchbay\Services\WorkflowValidator;

final class WorkflowController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $repo = new WorkflowRepository();
        $rows = $repo->all();
        foreach ($rows as &$row) $row['stats'] = $repo->stats((int) $row['id']);
        $this->view('workflows.index', ['title' => 'Workflows', 'workflows' => $rows]);
    }

    public function create(Request $request): never
    {
        $this->auth();
        $definition = (new WorkflowDefinition([
            ['type' => 'filter', 'field' => 'event.type', 'operator' => 'equals', 'value' => 'example'],
            ['type' => 'http', 'method' => 'POST', 'url' => 'https://example.com/webhook', 'headers' => [], 'body_mode' => 'current', 'timeout' => 15, 'retry' => ['max_attempts' => 3, 'delay_seconds' => 30]],
        ]))->toJson();
        $this->view('workflows.create', ['title' => 'New workflow', 'definition' => $definition]);
    }

    public function store(Request $request): never
    {
        $this->auth(); $this->csrf($request);
        $data = $this->validatedInput($request);
        if (isset($data['errors'])) {
            $_SESSION['_old_workflow'] = $request->post;
            $this->flash('error', implode(' ', $data['errors']));
            Response::redirect('/workflows/create');
        }
        $id = (new WorkflowRepository())->create($data);
        (new AuditService())->record('created', 'workflow', $id, ['name' => $data['name']]);
        $this->flash('success', 'Workflow created. The endpoint is ready to receive events.');
        Response::redirect('/workflows/' . $id);
    }

    public function show(Request $request, string $id): never
    {
        $this->auth();
        $repo = new WorkflowRepository();
        $workflow = $repo->find((int) $id);
        if (!$workflow) Response::html('<h1>404</h1>', 404);
        $executions = (new ExecutionRepository())->recentForWorkflow((int) $id, 20);
        $this->view('workflows.show', ['title' => $workflow['name'], 'workflow' => $workflow, 'stats' => $repo->stats((int) $id), 'executions' => $executions]);
    }

    public function edit(Request $request, string $id): never
    {
        $this->auth();
        $workflow = (new WorkflowRepository())->find((int) $id);
        if (!$workflow) Response::html('<h1>404</h1>', 404);
        $this->view('workflows.edit', ['title' => 'Edit ' . $workflow['name'], 'workflow' => $workflow, 'definition' => $workflow['definition_json']]);
    }

    public function update(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        $repo = new WorkflowRepository();
        $existing = $repo->find((int) $id);
        if (!$existing) Response::html('<h1>404</h1>', 404);
        $data = $this->validatedInput($request, $existing);
        if (isset($data['errors'])) {
            $this->flash('error', implode(' ', $data['errors']));
            Response::redirect('/workflows/' . $id . '/edit');
        }
        $repo->update((int) $id, $data);
        (new AuditService())->record('updated', 'workflow', (int) $id);
        $this->flash('success', 'Workflow updated.');
        Response::redirect('/workflows/' . $id);
    }

    public function destroy(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        (new WorkflowRepository())->delete((int) $id);
        (new AuditService())->record('deleted', 'workflow', (int) $id);
        $this->flash('success', 'Workflow deleted with its stored events and executions.');
        Response::redirect('/workflows');
    }

    public function rotateToken(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        $token = (new WorkflowRepository())->rotateToken((int) $id);
        (new AuditService())->record('rotated_endpoint_token', 'workflow', (int) $id);
        $this->flash('success', 'Endpoint token rotated. Update any senders using the old endpoint.');
        Response::redirect('/workflows/' . $id);
    }

    private function validatedInput(Request $request, ?array $existing = null): array
    {
        $name = trim((string) $request->input('name', ''));
        $definition = (string) $request->input('definition_json', '');
        $errors = (new WorkflowValidator())->validate($name, $definition);
        $originsText = trim((string) $request->input('allowed_origins', ''));
        $origins = $originsText === '' ? [] : array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', $originsText) ?: [])));
        foreach ($origins as $origin) {
            if (!filter_var($origin, FILTER_VALIDATE_URL)) $errors[] = 'Allowed origin is not a valid URL: ' . $origin;
        }
        if ($errors) return ['errors' => $errors];

        $requireSignature = $request->input('require_signature') === '1';
        $secret = trim((string) $request->input('signing_secret', ''));
        $encrypted = (string) ($existing['signing_secret_enc'] ?? '');
        if ($secret !== '') $encrypted = Crypto::encrypt($secret);
        if ($requireSignature && $encrypted === '') {
            $secret = 'whsec_' . Token::random(24);
            $encrypted = Crypto::encrypt($secret);
            $_SESSION['_generated_secret'] = $secret;
        }
        return [
            'name' => $name,
            'description' => trim((string) $request->input('description', '')),
            'is_active' => $request->input('is_active') === '1',
            'definition_json' => (new WorkflowDefinition(json_decode_array($definition, [])['steps'] ?? []))->toJson(),
            'allowed_origins' => $origins,
            'require_signature' => $requireSignature,
            'signing_secret_enc' => $encrypted,
        ];
    }
}
