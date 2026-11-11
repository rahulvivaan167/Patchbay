<?php

declare(strict_types=1);

namespace Patchbay\Controllers;

use Patchbay\Http\Request;
use Patchbay\Http\Response;
use Patchbay\Services\WebhookReceiver;
use Patchbay\Repositories\WorkflowRepository;
use RuntimeException;
use Throwable;

final class WebhookController
{
    public function options(Request $request, string $token): never
    {
        $workflow = (new WorkflowRepository())->findByToken($token);
        if (!$workflow) Response::json(['error' => 'not_found'], 404);
        $origin = rtrim((string) $request->header('Origin', ''), '/');
        $allowed = json_decode_array($workflow['allowed_origins_json'] ?? '[]', []);
        if ($allowed && $origin !== '' && !in_array($origin, array_map(static fn($v) => rtrim((string) $v, '/'), $allowed), true)) {
            Response::json(['error' => 'origin_not_allowed'], 403);
        }
        if ($allowed && $origin !== '') header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
        header('Access-Control-Allow-Headers: Content-Type, X-Patchbay-Signature');
        header('Access-Control-Allow-Methods: POST, PUT, PATCH, OPTIONS');
        Response::noContent();
    }

    public function receive(Request $request, string $token): never
    {
        try {
            $result = (new WebhookReceiver())->receive($request, $token);
            if (!empty($result['cors_origin'])) { header('Access-Control-Allow-Origin: ' . $result['cors_origin']); header('Vary: Origin'); }
            Response::json(['accepted' => true, 'request_id' => $result['request_id'], 'execution_id' => $result['execution_id']], 202);
        } catch (RuntimeException $e) {
            $code = in_array($e->getCode(), [401, 403, 404, 413, 422, 429, 500], true) ? $e->getCode() : 400;
            Response::json(['accepted' => false, 'error' => $e->getMessage()], $code);
        } catch (Throwable $e) {
            error_log($e->__toString());
            Response::json(['accepted' => false, 'error' => 'Internal error.'], 500);
        }
    }
}
