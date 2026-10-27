<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$files = glob(__DIR__ . '/Unit/*Test.php') ?: [];
$passed = 0;
$failed = 0;
foreach ($files as $file) {
    $name = basename($file, '.php');
    try {
        $test = require $file;
        $test();
        fwrite(STDOUT, "PASS {$name}\n");
        $passed++;
    } catch (Throwable $e) {
        fwrite(STDERR, "FAIL {$name}: {$e->getMessage()}\n");
        $failed++;
    }
}
fwrite(STDOUT, "\n{$passed} passed, {$failed} failed\n");
exit($failed > 0 ? 1 : 0);
