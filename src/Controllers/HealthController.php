<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Database;
use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Throwable;

final class HealthController
{
    public function show(Request $request): never
    {
        try {
            Database::connection()->query('SELECT 1');
            Response::json(['status' => 'ok', 'app' => config('app.name', 'Patchbay'), 'time' => now()]);
        } catch (Throwable) {
            Response::json(['status' => 'error'], 503);
        }
    }
}
