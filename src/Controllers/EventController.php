<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\EventRepository;
use Patchbay\Repositories\WorkflowRepository;

final class EventController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $workflowId = (int) $request->input('workflow_id', 0);
        $this->view('events.index', [
            'title' => 'Events',
            'events' => (new EventRepository())->recent(100, $workflowId ?: null),
            'workflows' => (new WorkflowRepository())->all(),
            'workflowId' => $workflowId,
        ]);
    }

    public function show(Request $request, string $id): never
    {
        $this->auth();
        $event = (new EventRepository())->find((int) $id);
        if (!$event) Response::html('<h1>404</h1>', 404);
        $this->view('events.show', ['title' => 'Event ' . $event['request_id'], 'event' => $event]);
    }
}
