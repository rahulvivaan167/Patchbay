<?php

declare(strict_types=1);

namespace Patchbay\Domain;

use InvalidArgumentException;

final class WorkflowDefinition
{
    public function __construct(public readonly array $steps)
    {
        if (count($steps) > 50) throw new InvalidArgumentException('A workflow may contain at most 50 steps.');
        foreach ($steps as $i => $step) {
            if (!is_array($step) || !isset($step['type'])) throw new InvalidArgumentException("Step {$i} is invalid.");
            if (!in_array($step['type'], ['filter', 'transform', 'http', 'delay', 'log'], true)) {
                throw new InvalidArgumentException('Unsupported step type: ' . $step['type']);
            }
        }
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode_array($json, []);
        return new self(is_array($data['steps'] ?? null) ? $data['steps'] : []);
    }

    public function toJson(): string
    {
        return json_encode_safe(['steps' => $this->steps], JSON_PRETTY_PRINT);
    }
}
