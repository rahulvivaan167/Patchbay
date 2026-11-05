<?php

declare(strict_types=1);

namespace Patchbay;

final class Config
{
    private static array $items = [];

    public static function load(string $configPath): void
    {
        foreach (glob(rtrim($configPath, '/\\') . '/*.php') ?: [] as $file) {
            self::$items[pathinfo($file, PATHINFO_FILENAME)] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return $default;
            $value = $value[$segment];
        }
        return $value;
    }
}
