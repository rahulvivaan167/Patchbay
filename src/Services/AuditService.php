<?php

declare(strict_types=1);

namespace Patchbay\Services;

use Patchbay\Auth\Auth;
use Patchbay\Repositories\AuditRepository;

final class AuditService
{
    public function record(string $action, string $entityType, ?int $entityId = null, array $context = []): void
    {
        $user = Auth::user();
        (new AuditRepository())->write($user ? (int) $user['id'] : null, $action, $entityType, $entityId, $context);
    }
}
