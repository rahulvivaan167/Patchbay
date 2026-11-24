<?php

declare(strict_types=1);

namespace Patchbay\Services;

final class TemplateRenderer
{
    public function renderString(string $value, array $context): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_.-]+)\s*\}\}/', static function (array $m) use ($context): string {
            $resolved = array_get_dot($context, $m[1], '');
            if (is_array($resolved) || is_object($resolved)) return json_encode_safe($resolved);
            return (string) $resolved;
        }, $value);
    }

    public function renderArray(array $input, array $context): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            $out[$key] = is_array($value) ? $this->renderArray($value, $context) : (is_string($value) ? $this->renderString($value, $context) : $value);
        }
        return $out;
    }
}
