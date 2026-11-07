<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Services\DashboardService;

final class DashboardController extends Controller
{
    public function index(Request $request): never
    {
        $this->auth();
        $this->view('dashboard.index', ['title' => 'Dashboard', 'metrics' => (new DashboardService())->metrics()]);
    }
}
