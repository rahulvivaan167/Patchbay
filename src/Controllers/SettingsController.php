<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Auth\Auth;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\AuditRepository;
use Patchbay\Repositories\SettingRepository;
use Patchbay\Repositories\UserRepository;
use Patchbay\Services\AuditService;

final class SettingsController extends Controller
{
    public function index(Request $request): never
    {
        $user = $this->auth();
        $this->view('settings.index', [
            'title' => 'Settings',
            'settings' => (new SettingRepository())->all(),
            'audits' => (new AuditRepository())->recent(20),
            'user' => $user,
        ]);
    }

    public function update(Request $request): never
    {
        $user = $this->auth(); $this->csrf($request);
        $retention = max(1, min(3650, (int) $request->input('retention_days', 30)));
        (new SettingRepository())->set('retention_days', (string) $retention);
        $password = (string) $request->input('new_password', '');
        if ($password !== '') {
            if (strlen($password) < 10) {
                $this->flash('error', 'New password must contain at least 10 characters.');
                Response::redirect('/settings');
            }
            (new UserRepository())->updatePassword((int) $user['id'], $password);
        }
        (new AuditService())->record('updated', 'settings');
        $this->flash('success', 'Settings saved.');
        Response::redirect('/settings');
    }
}
