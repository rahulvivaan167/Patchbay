<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\ApiKeyRepository;
use Patchbay\Services\AuditService;

final class ApiKeyController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $plain = $_SESSION['_new_api_key'] ?? null;
        unset($_SESSION['_new_api_key']);
        $this->view('api-keys.index', ['title' => 'API keys', 'keys' => (new ApiKeyRepository())->all(), 'newKey' => $plain]);
    }

    public function store(Request $request): never
    {
        $this->auth(); $this->csrf($request);
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            $this->flash('error', 'Give the API key a name.');
            Response::redirect('/api-keys');
        }
        $key = (new ApiKeyRepository())->create($name);
        $_SESSION['_new_api_key'] = $key['plain'];
        (new AuditService())->record('created', 'api_key', (int) $key['id'], ['name' => $name]);
        Response::redirect('/api-keys');
    }

    public function revoke(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        (new ApiKeyRepository())->revoke((int) $id);
        (new AuditService())->record('revoked', 'api_key', (int) $id);
        $this->flash('success', 'API key revoked.');
        Response::redirect('/api-keys');
    }
}
