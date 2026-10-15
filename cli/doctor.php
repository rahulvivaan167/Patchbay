<?php

declare(strict_types=1);

use Patchbay\Bootstrap;
use Patchbay\Database;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot(false);

$checks = [];
$add = static function (string $label, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = [$label, $ok, $detail];
};

$add('PHP >= 8.2', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);
$add('PDO extension', extension_loaded('PDO'));
$driver = (string) config('database.driver', 'sqlite');
$requiredDriver = $driver === 'mysql' ? 'pdo_mysql' : 'pdo_sqlite';
$add($requiredDriver . ' extension', extension_loaded($requiredDriver));
$add('OpenSSL extension', extension_loaded('openssl'));
$add('Storage writable', is_writable(base_path('storage')));
$key = (string) config('app.key', '');
$add('APP_KEY configured', strlen($key) >= 24, $key === '' ? 'missing' : 'length ' . strlen($key));

try {
    Database::connection()->query('SELECT 1');
    $add('Database connection', true, $driver);
} catch (Throwable $e) {
    $add('Database connection', false, $e->getMessage());
}

$failed = false;
foreach ($checks as [$label, $ok, $detail]) {
    $failed = $failed || !$ok;
    fwrite(STDOUT, sprintf("[%s] %-24s %s\n", $ok ? 'OK' : 'FAIL', $label, $detail));
}
exit($failed ? 1 : 0);
