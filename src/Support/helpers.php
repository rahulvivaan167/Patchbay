<?php

declare(strict_types=1);

use Patchbay\Config;

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path === '' ? $base : $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('env_bool')) {
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env($key, $default ? 'true' : 'false');
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}

if (!function_exists('env_int')) {
    function env_int(string $key, int $default): int
    {
        $value = env($key, (string) $default);
        return is_numeric($value) ? (int) $value : $default;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('now')) {
    function now(): string
    {
        return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    }
}

if (!function_exists('json_encode_safe')) {
    function json_encode_safe(mixed $value, int $flags = 0): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | $flags);
        } catch (JsonException) {
            return '{}';
        }
    }
}

if (!function_exists('json_decode_array')) {
    function json_decode_array(?string $value, array $default = []): array
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : $default;
        } catch (JsonException) {
            return $default;
        }
    }
}

if (!function_exists('array_get_dot')) {
    function array_get_dot(array $data, string $path, mixed $default = null): mixed
    {
        if ($path === '') return $data;
        $cursor = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) return $default;
            $cursor = $cursor[$segment];
        }
        return $cursor;
    }
}

if (!function_exists('array_set_dot')) {
    function array_set_dot(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $cursor =& $data;
        foreach ($segments as $segment) {
            if ($segment === '') continue;
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) $cursor[$segment] = [];
            $cursor =& $cursor[$segment];
        }
        $cursor = $value;
    }
}
