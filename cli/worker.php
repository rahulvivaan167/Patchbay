<?php

declare(strict_types=1);

use Patchbay\Bootstrap;
use Patchbay\Database;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Services\WorkflowEngine;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot(false);
Database::migrate();

$once = in_array('--once', $argv, true);
$limit = 20;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) $limit = max(1, min(100, (int) substr($arg, 8)));
}

$lockPath = base_path('storage/cache/worker.lock');
$lock = fopen($lockPath, 'c+');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Another Patchbay worker already owns the worker lock.\n");
    exit(2);
}

$repo = new ExecutionRepository();
$engine = new WorkflowEngine();
$sleep = max(1, (int) config('app.worker_sleep', 3));

fwrite(STDOUT, "Patchbay worker started" . ($once ? " (once)" : "") . ".\n");
do {
    $recovered = $repo->recoverStale(300);
    if ($recovered > 0) fwrite(STDOUT, '[' . date('c') . "] recovered {$recovered} stale execution(s)\n");
    $ids = $repo->due($limit);
    foreach ($ids as $id) {
        fwrite(STDOUT, '[' . date('c') . "] execution #{$id}\n");
        try {
            $engine->process($id);
        } catch (Throwable $e) {
            fwrite(STDERR, "Execution #{$id} crashed: {$e->getMessage()}\n");
        }
    }
    if ($once) break;
    if (!$ids) sleep($sleep);
} while (true);

flock($lock, LOCK_UN);
fclose($lock);
