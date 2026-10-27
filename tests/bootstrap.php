<?php

declare(strict_types=1);

use Patchbay\Bootstrap;

require_once dirname(__DIR__) . '/src/Bootstrap.php';
Bootstrap::boot(false);

function assert_true(bool $condition, string $message = 'Expected true'): void
{
    if (!$condition) throw new RuntimeException($message);
}

function assert_same(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $suffix = $message !== '' ? ' ' . $message : '';
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.' . $suffix);
    }
}
