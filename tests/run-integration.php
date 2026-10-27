<?php

declare(strict_types=1);

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDOUT, "SKIP integration tests: pdo_sqlite is not installed.\n");
    exit(0);
}

$database = dirname(__DIR__) . '/storage/cache/test.sqlite';
@unlink($database);
putenv('DB_DRIVER=sqlite');
putenv('DB_DATABASE=storage/cache/test.sqlite');
putenv('APP_KEY=patchbay-integration-key-change-me-123456');
$_ENV['DB_DRIVER'] = 'sqlite';
$_ENV['DB_DATABASE'] = 'storage/cache/test.sqlite';
$_ENV['APP_KEY'] = 'patchbay-integration-key-change-me-123456';

require_once dirname(__DIR__) . '/src/Bootstrap.php';
\Patchbay\Bootstrap::boot(false);
\Patchbay\Database::migrate();

function assert_true(bool $condition, string $message = 'Expected true'): void
{
    if (!$condition) throw new RuntimeException($message);
}
function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '. ' . $message);
}

$files = glob(__DIR__ . '/Integration/*Test.php') ?: [];
$passed = $failed = 0;
foreach ($files as $file) {
    try {
        (require $file)();
        fwrite(STDOUT, 'PASS ' . basename($file, '.php') . "\n");
        $passed++;
    } catch (Throwable $e) {
        fwrite(STDERR, 'FAIL ' . basename($file, '.php') . ': ' . $e->getMessage() . "\n");
        $failed++;
    }
}
@unlink($database);
fwrite(STDOUT, "\n{$passed} passed, {$failed} failed\n");
exit($failed ? 1 : 0);
