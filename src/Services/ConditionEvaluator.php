<?php

declare(strict_types=1);

namespace Patchbay\Services;

final class ConditionEvaluator
{
    public function evaluate(array $payload, array $condition): bool
    {
        $field = (string) ($condition['field'] ?? '');
        $operator = (string) ($condition['operator'] ?? 'equals');
        $expected = $condition['value'] ?? null;
        $actual = array_get_dot($payload, $field);

        return match ($operator) {
            'equals' => $actual == $expected,
            'strict_equals' => $actual === $expected,
            'not_equals' => $actual != $expected,
            'contains' => is_string($actual) && str_contains($actual, (string) $expected),
            'not_contains' => is_string($actual) && !str_contains($actual, (string) $expected),
            'starts_with' => is_string($actual) && str_starts_with($actual, (string) $expected),
            'ends_with' => is_string($actual) && str_ends_with($actual, (string) $expected),
            'exists' => array_get_dot($payload, $field, '__patchbay_missing__') !== '__patchbay_missing__',
            'not_exists' => array_get_dot($payload, $field, '__patchbay_missing__') === '__patchbay_missing__',
            'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
            'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            default => false,
        };
    }
}
