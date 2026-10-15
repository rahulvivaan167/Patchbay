<?php

declare(strict_types=1);

use Patchbay\Bootstrap;
use Patchbay\Database;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot(false);

try {
    Database::migrate();
    fwrite(STDOUT, "Patchbay database schema is up to date.\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: {$e->getMessage()}\n");
    exit(1);
}
