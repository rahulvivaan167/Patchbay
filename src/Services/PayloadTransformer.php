<?php

declare(strict_types=1);

namespace Patchbay\Services;

final class PayloadTransformer
{
    public function transform(array $payload, array $step): array
    {
        $mode = (string) ($step['mode'] ?? 'map');
        if ($mode === 'merge') {
            $values = is_array($step['values'] ?? null) ? $step['values'] : [];
            return array_replace_recursive($payload, $values);
        }
        if ($mode === 'pick') {
            $out = [];
            foreach (($step['fields'] ?? []) as $field) {
                $field = (string) $field;
                $value = array_get_dot($payload, $field, null);
                array_set_dot($out, $field, $value);
            }
            return $out;
        }
        if ($mode === 'remove') {
            $out = $payload;
            foreach (($step['fields'] ?? []) as $field) $this->forget($out, (string) $field);
            return $out;
        }

        $out = [];
        $mapping = is_array($step['mapping'] ?? null) ? $step['mapping'] : [];
        foreach ($mapping as $destination => $source) {
            $value = is_string($source) && str_starts_with($source, '=')
                ? substr($source, 1)
                : array_get_dot($payload, (string) $source);
            array_set_dot($out, (string) $destination, $value);
        }
        return $out;
    }

    private function forget(array &$data, string $path): void
    {
        $parts = explode('.', $path);
        $last = array_pop($parts);
        $cursor =& $data;
        foreach ($parts as $part) {
            if (!isset($cursor[$part]) || !is_array($cursor[$part])) return;
            $cursor =& $cursor[$part];
        }
        unset($cursor[$last]);
    }
}
