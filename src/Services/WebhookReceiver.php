<?php

declare(strict_types=1);

namespace Patchbay\Services;

use Patchbay\Http\Request;
use Patchbay\Repositories\EventRepository;
use Patchbay\Repositories\ExecutionRepository;
use Patchbay\Repositories\WorkflowRepository;
use Patchbay\Security\Crypto;
use Patchbay\Security\RateLimiter;
use RuntimeException;

final class WebhookReceiver
{
    public function receive(Request $request, string $token): array
    {
        $workflow = (new WorkflowRepository())->findByToken($token);
        if (!$workflow) throw new RuntimeException('Workflow not found or disabled.', 404);

        $limit = (int) config('security.webhook_rate_limit', 120);
        if (!(new RateLimiter())->hit('hook:' . $workflow['id'] . ':' . $request->ip(), $limit, 60)) {
            throw new RuntimeException('Rate limit exceeded.', 429);
        }

        $raw = $request->raw();
        if (strlen($raw) > (int) config('app.max_payload_bytes', 1048576)) {
            throw new RuntimeException('Payload too large.', 413);
        }
        $this->validateOrigin($request, $workflow);
        $this->validateSignature($request, $workflow, $raw);

        $contentType = strtolower((string) $request->header('Content-Type', ''));
        if (str_contains($contentType, 'application/json')) {
            $payload = $request->json();
            if ($raw !== '' && $payload === []) {
                try { json_decode($raw, true, 512, JSON_THROW_ON_ERROR); } catch (\JsonException) { throw new RuntimeException('Invalid JSON payload.', 422); }
            }
        } elseif (!empty($request->post)) {
            $payload = $request->post;
        } else {
            $payload = ['raw' => $raw];
        }

        $requestId = 'evt_' . bin2hex(random_bytes(10));
        $eventId = (new EventRepository())->create([
            'workflow_id' => (int) $workflow['id'],
            'request_id' => $requestId,
            'method' => $request->method,
            'content_type' => substr($contentType, 0, 255),
            'headers' => $this->redactHeaders($request->headers()),
            'query' => $request->query,
            'payload' => $payload,
            'raw_body' => substr($raw, 0, (int) config('app.max_payload_bytes', 1048576)),
            'source_ip' => $request->ip(),
        ]);
        $executionId = (new ExecutionRepository())->create((int) $workflow['id'], $eventId);
        $allowedOrigins = json_decode_array($workflow['allowed_origins_json'] ?? '[]', []);
        $origin = rtrim((string) $request->header('Origin', ''), '/');
        $corsOrigin = ($origin !== '' && in_array($origin, array_map(static fn($v) => rtrim((string) $v, '/'), $allowedOrigins), true)) ? $origin : null;
        return ['event_id' => $eventId, 'execution_id' => $executionId, 'request_id' => $requestId, 'cors_origin' => $corsOrigin];
    }

    private function validateOrigin(Request $request, array $workflow): void
    {
        $allowed = json_decode_array($workflow['allowed_origins_json'] ?? '[]', []);
        if (!$allowed) return;
        $origin = rtrim((string) $request->header('Origin', ''), '/');
        if ($origin !== '' && !in_array($origin, array_map(static fn($v) => rtrim((string) $v, '/'), $allowed), true)) {
            throw new RuntimeException('Origin is not allowed.', 403);
        }
    }

    private function validateSignature(Request $request, array $workflow, string $raw): void
    {
        if (!(bool) ($workflow['require_signature'] ?? false)) return;
        $secret = Crypto::decrypt((string) ($workflow['signing_secret_enc'] ?? ''));
        if ($secret === '') throw new RuntimeException('Workflow signature secret is unavailable.', 500);
        $provided = (string) $request->header('X-Patchbay-Signature', '');
        $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
        if ($provided === '' || !hash_equals($expected, $provided)) throw new RuntimeException('Invalid webhook signature.', 401);
    }

    private function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            $lower = strtolower((string) $name);
            if ($lower === 'cookie' || str_contains($lower, 'authorization') || str_contains($lower, 'api-key') || str_contains($lower, 'token')) {
                $headers[$name] = '[redacted]';
            }
        }
        return $headers;
    }
}
