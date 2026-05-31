<?php

declare(strict_types=1);

namespace MrRijal\MicroService\Http;

final class Router
{
    /** @var array<string, array<string, callable|class-string>> */
    private array $routes = [];

    public function get(string $path, callable|string $handler): self
    {
        return $this->add('GET', $path, $handler);
    }

    public function add(string $method, string $path, callable|string $handler): self
    {
        $this->routes[strtoupper($method)][$this->normalizePath($path)] = $handler;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function dispatch(string $method, string $path): array
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);
        $handler = $this->routes[$method][$path] ?? null;

        if ($handler === null) {
            http_response_code(404);

            return [
                'status' => 'error',
                'message' => 'Route not found.',
            ];
        }

        if (is_string($handler)) {
            $handler = new $handler;
        }

        $response = $handler();

        return is_array($response) ? $response : ['status' => 'ok'];
    }

    private function normalizePath(string $path): string
    {
        $path = '/'.trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
