<?php

declare(strict_types=1);

use Patchbay\Bootstrap;
use Patchbay\Controllers\ApiController;
use Patchbay\Controllers\ApiKeyController;
use Patchbay\Controllers\AuthController;
use Patchbay\Controllers\DashboardController;
use Patchbay\Controllers\EventController;
use Patchbay\Controllers\ExecutionController;
use Patchbay\Controllers\HealthController;
use Patchbay\Controllers\SettingsController;
use Patchbay\Controllers\SecretController;
use Patchbay\Controllers\SetupController;
use Patchbay\Controllers\WebhookController;
use Patchbay\Controllers\WorkflowController;
use Patchbay\Database;
use Patchbay\Http\Request;
use Patchbay\Http\Router;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot();
Database::ensureSchema();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");

$router = new Router();

$router->get('/health', [HealthController::class, 'show']);
$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'store']);
$router->get('/login', [AuthController::class, 'show']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/workflows', [WorkflowController::class, 'index']);
$router->get('/workflows/create', [WorkflowController::class, 'create']);
$router->post('/workflows', [WorkflowController::class, 'store']);
$router->get('/workflows/{id}', [WorkflowController::class, 'show']);
$router->get('/workflows/{id}/edit', [WorkflowController::class, 'edit']);
$router->add('PUT', '/workflows/{id}', [WorkflowController::class, 'update']);
$router->delete('/workflows/{id}', [WorkflowController::class, 'destroy']);
$router->post('/workflows/{id}/rotate-token', [WorkflowController::class, 'rotateToken']);

$router->get('/events', [EventController::class, 'index']);
$router->get('/events/{id}', [EventController::class, 'show']);
$router->get('/executions', [ExecutionController::class, 'index']);
$router->get('/executions/{id}', [ExecutionController::class, 'show']);
$router->post('/executions/{id}/retry', [ExecutionController::class, 'retry']);

$router->get('/secrets', [SecretController::class, 'index']);
$router->post('/secrets', [SecretController::class, 'store']);
$router->delete('/secrets/{id}', [SecretController::class, 'destroy']);

$router->get('/api-keys', [ApiKeyController::class, 'index']);
$router->post('/api-keys', [ApiKeyController::class, 'store']);
$router->post('/api-keys/{id}/revoke', [ApiKeyController::class, 'revoke']);
$router->get('/settings', [SettingsController::class, 'index']);
$router->post('/settings', [SettingsController::class, 'update']);

$router->post('/hook/{token}', [WebhookController::class, 'receive']);
$router->add('PUT', '/hook/{token}', [WebhookController::class, 'receive']);
$router->add('PATCH', '/hook/{token}', [WebhookController::class, 'receive']);
$router->add('OPTIONS', '/hook/{token}', [WebhookController::class, 'options']);

$router->get('/api/v1/workflows', [ApiController::class, 'workflows']);
$router->get('/api/v1/events', [ApiController::class, 'events']);
$router->get('/api/v1/executions', [ApiController::class, 'executions']);
$router->get('/api/v1/executions/{id}', [ApiController::class, 'execution']);

$router->dispatch(Request::capture());
