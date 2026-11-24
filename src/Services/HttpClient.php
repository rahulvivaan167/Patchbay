<?php

declare(strict_types=1);

namespace Patchbay\Services;

use RuntimeException;

final class HttpClient
{
    public function send(string $method, string $url, array $headers, mixed $body, int $timeout): array
    {
        $method = strtoupper($method);
        $headers = array_change_key_case($headers, CASE_LOWER);
        if (!isset($headers['content-type'])) $headers['content-type'] = 'application/json';
        $headers['user-agent'] = (string) config('app.http_user_agent', 'Patchbay/1.0');
        $encodedBody = is_string($body) ? $body : json_encode_safe($body);
        $headerLines = [];
        foreach ($headers as $name => $value) $headerLines[] = $this->headerName((string) $name) . ': ' . (string) $value;
        $started = microtime(true);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_HEADER => true,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            ]);
            if (!in_array($method, ['GET', 'HEAD'], true)) curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedBody);
            $response = curl_exec($ch);
            if ($response === false) {
                $message = curl_error($ch);
                curl_close($ch);
                throw new RuntimeException('HTTP request failed: ' . $message);
            }
            $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseBody = substr((string) $response, $headerSize);
            curl_close($ch);
        } else {
            $opts = ['http' => [
                'method' => $method,
                'header' => implode("\r\n", $headerLines),
                'content' => in_array($method, ['GET', 'HEAD'], true) ? '' : $encodedBody,
                'timeout' => $timeout,
                'ignore_errors' => true,
                'follow_location' => 0,
            ]];
            $responseBody = @file_get_contents($url, false, stream_context_create($opts));
            if ($responseBody === false) throw new RuntimeException('HTTP request failed using stream transport.');
            $status = 0;
            foreach ($http_response_header ?? [] as $line) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $m)) $status = (int) $m[1];
            }
        }

        return [
            'status' => $status,
            'body' => substr((string) $responseBody, 0, 65536),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    private function headerName(string $name): string
    {
        return implode('-', array_map('ucfirst', explode('-', strtolower($name))));
    }
}
