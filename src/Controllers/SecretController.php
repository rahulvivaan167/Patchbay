<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\SecretRepository;
use Patchbay\Services\AuditService;

final class SecretController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $this->view('secrets.index', ['title' => 'Secrets', 'secrets' => (new SecretRepository())->all()]);
    }

    public function store(Request $request): never
    {
        $this->auth(); $this->csrf($request);
        $name = strtoupper(trim((string) $request->input('name', '')));
        $value = (string) $request->input('value', '');
        if (!preg_match('/^[A-Z][A-Z0-9_]{1,79}$/', $name)) {
            $this->flash('error', 'Secret names must use 2–80 uppercase letters, numbers, or underscores and start with a letter.');
            Response::redirect('/secrets');
        }
        if ($value === '') {
            $this->flash('error', 'Secret value cannot be empty.');
            Response::redirect('/secrets');
        }
        $id = (new SecretRepository())->upsert($name, $value);
        (new AuditService())->record('saved', 'secret', $id, ['name' => $name]);
        $this->flash('success', 'Secret saved. Its value is encrypted and will not be shown in the dashboard.');
        Response::redirect('/secrets');
    }

    public function destroy(Request $request, string $id): never
    {
        $this->auth(); $this->csrf($request);
        (new SecretRepository())->delete((int) $id);
        (new AuditService())->record('deleted', 'secret', (int) $id);
        $this->flash('success', 'Secret deleted.');
        Response::redirect('/secrets');
    }
}
