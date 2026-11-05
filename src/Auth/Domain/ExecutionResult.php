<?php

declare(strict_types=1);

namespace Patchbay\Domain;

final class ExecutionResult
{
    public function __construct(
        public readonly string $status,
        public readonly array $payload,
        public readonly ?string $message = null,
    ) {}
}
