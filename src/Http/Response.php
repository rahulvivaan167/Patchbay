<?php

declare(strict_types=1);

namespace Patchbay\Http;

final class Response
{
    public static function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode_safe($data);
        exit;
    }

    public static function redirect(string $location, int $status = 302): never
    {
        http_response_code($status);
        header('Location: ' . $location);
        exit;
    }

    public static function noContent(int $status = 204): never
    {
        http_response_code($status);
        exit;
    }
}
