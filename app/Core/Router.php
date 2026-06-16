<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Simple, fast regex router with middleware support and route groups.
 */
final class Router
{
    private array $routes = [];
    private array $groupStack = [];

    public function get(string $uri, mixed $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, mixed $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, mixed $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, mixed $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    private function addRoute(string $method, string $uri, mixed $action, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groupStack as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }

        $uri = '/' . trim($prefix . $uri, '/');
        $uri = $uri === '/' ? '/' : rtrim($uri, '/');

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'action'     => $action,
            'middleware' => array_merge($groupMiddleware, $middleware),
            'pattern'    => $this->compile($uri),
        ];
    }

    private function compile(string $uri): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->runMiddleware($route['middleware'], $request);
                $this->runAction($route['action'], $request, $params);
                return;
            }
        }

        Response::notFound();
    }

    private function runMiddleware(array $middleware, Request $request): void
    {
        foreach ($middleware as $mw) {
            $class = "App\\Middleware\\{$mw}";
            if (!class_exists($class)) {
                throw new RuntimeException("Middleware not found: {$class}");
            }
            (new $class())->handle($request);
        }
    }

    private function runAction(mixed $action, Request $request, array $params): void
    {
        if (is_callable($action)) {
            $action($request, ...array_values($params));
            return;
        }

        [$controller, $method] = is_array($action) ? $action : explode('@', $action);
        $class = str_contains($controller, '\\') ? $controller : "App\\Controllers\\{$controller}";

        if (!class_exists($class)) {
            throw new RuntimeException("Controller not found: {$class}");
        }

        $instance = new $class();
        if (!method_exists($instance, $method)) {
            throw new RuntimeException("Method not found: {$class}@{$method}");
        }

        $instance->{$method}($request, ...array_values($params));
    }
}
