<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Auth\Auth;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\UserRepository;

final class SetupController extends Controller
{
    public function show(Request $request): never
    {
        if ((new UserRepository())->count() > 0) Response::redirect('/login');
        $this->view('setup.index', ['title' => 'Set up Patchbay'], 'auth');
    }

    public function store(Request $request): never
    {
        $this->csrf($request);
        $users = new UserRepository();
        if ($users->count() > 0) Response::redirect('/login');
        $name = trim((string) $request->input('name', ''));
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
            $this->flash('error', 'Enter a name, valid email, and a password with at least 10 characters.');
            Response::redirect('/setup');
        }
        $users->create($name, $email, $password);
        Auth::attempt($email, $password);
        $this->flash('success', 'Patchbay is ready. Create your first workflow.');
        Response::redirect('/workflows/create');
    }
}
