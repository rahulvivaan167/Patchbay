<?php

declare(strict_types=1);

namespace Patchbay\Http;

final class Request
{
    private ?string $rawBody = null;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $post,
        public readonly array $server,
        public readonly array $cookies,
        public readonly array $files,
    ) {}

    public static function capture(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $path, $_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function json(): array
    {
        $decoded = json_decode_array($this->raw(), []);
        return $decoded;
    }

    public function raw(): string
    {
        if ($this->rawBody === null) $this->rawBody = (string) file_get_contents('php://input');
        return $this->rawBody;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (strtolower($name) === 'content-type') $key = 'CONTENT_TYPE';
        if (strtolower($name) === 'authorization' && isset($this->server['REDIRECT_HTTP_AUTHORIZATION'])) return (string) $this->server['REDIRECT_HTTP_AUTHORIZATION'];
        return isset($this->server[$key]) ? (string) $this->server[$key] : $default;
    }

    public function headers(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        if (isset($this->server['CONTENT_TYPE'])) $headers['content-type'] = (string) $this->server['CONTENT_TYPE'];
        return $headers;
    }

    public function ip(): string
    {
        if ((bool) config('app.trust_proxy', false)) {
            $forwarded = $this->header('X-Forwarded-For');
            if ($forwarded) return trim(explode(',', $forwarded)[0]);
        }
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', (string) $auth, $m)) return trim($m[1]);
        return null;
    }

    public function acceptsJson(): bool
    {
        return str_contains((string) $this->header('Accept', ''), 'application/json');
    }
}
