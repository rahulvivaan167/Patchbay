<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Auth\Auth;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Repositories\UserRepository;
use Patchbay\Security\RateLimiter;

final class AuthController extends Controller
{
    public function show(Request $request): never
    {
        if ((new UserRepository())->count() === 0) Response::redirect('/setup');
        if (Auth::check()) Response::redirect('/');
        $this->view('auth.login', ['title' => 'Sign in'], 'auth');
    }

    public function login(Request $request): never
    {
        $this->csrf($request);
        $email = strtolower(trim((string) $request->input('email', '')));
        $password = (string) $request->input('password', '');
        $limiter = new RateLimiter();
        if (!$limiter->hit('login:' . $request->ip() . ':' . $email, (int) config('security.login_rate_limit', 10), (int) config('security.login_window_seconds', 900))) {
            $this->flash('error', 'Too many sign-in attempts. Try again later.');
            Response::redirect('/login');
        }
        if (!Auth::attempt($email, $password)) {
            $this->flash('error', 'Email or password is incorrect.');
            Response::redirect('/login');
        }
        Response::redirect('/');
    }

    public function logout(Request $request): never
    {
        $this->csrf($request);
        Auth::logout();
        Response::redirect('/login');
    }
}
