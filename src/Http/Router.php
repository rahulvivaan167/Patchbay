<?php

declare(strict_types=1);

namespace Patchbay\Http;

final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): void { $this->add('GET', $pattern, $handler); }
    public function post(string $pattern, callable|array $handler): void { $this->add('POST', $pattern, $handler); }
    public function delete(string $pattern, callable|array $handler): void { $this->add('DELETE', $pattern, $handler); }

    public function add(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [$method, $pattern, $handler];
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method;
        if ($method === 'POST' && isset($request->post['_method'])) $method = strtoupper((string) $request->post['_method']);

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) continue;
            $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
            if (preg_match('#^' . $regex . '$#', $request->path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    return (new $class())->{$action}($request, ...array_values($params));
                }
                return $handler($request, ...array_values($params));
            }
        }
        return Response::html('<h1>404</h1><p>Page not found.</p>', 404);
    }
}
