<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Auth\Auth;
use Patchbay\Http\Flash;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Http\View;
use Patchbay\Security\Csrf;

abstract class Controller
{
    protected function auth(): array
    {
        Auth::requireLogin();
        return Auth::user() ?? [];
    }

    protected function csrf(Request $request): void
    {
        if (!Csrf::verify($request)) Response::html('<h1>419</h1><p>Session token expired. Go back and try again.</p>', 419);
    }

    protected function view(string $name, array $data = [], string $layout = 'app'): never
    {
        $data['currentUser'] = Auth::user();
        $data['flashes'] = Flash::pull();
        Response::html(View::render($name, $data, $layout));
    }

    protected function flash(string $type, string $message): void
    {
        Flash::set($type, $message);
    }
}
