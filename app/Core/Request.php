<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulates the incoming HTTP request and provides safe accessors.
 */
final class Request
{
    private array $query;
    private array $body;
    private array $server;
    private array $files;
    private array $headers;

    public function __construct()
    {
        $this->query = $_GET;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    private function parseBody(): array
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        // Method spoofing support for forms
        if ($method === 'POST' && isset($this->body['_method'])) {
            $method = strtoupper((string) $this->body['_method']);
        }
        return $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';
        return '/' . trim($uri, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        $result = [];
        $all = $this->all();
        foreach ($keys as $key) {
            $result[$key] = $all[$key] ?? null;
        }
        return $result;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key, ?string $default = null): ?string
    {
        // Exact match first, then case-insensitive (PHP title-cases header
        // names, e.g. "X-CSRF-TOKEN" arrives as "X-Csrf-Token").
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        $lower = strtolower($key);
        foreach ($this->headers as $name => $value) {
            if (strtolower($name) === $lower) {
                return $value;
            }
        }
        return $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('Authorization', '');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($this->server[$key])) {
                $ip = explode(',', $this->server[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr((string) ($this->server['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With', '') ?? '') === 'xmlhttprequest'
            || str_contains($this->header('Accept', '') ?? '', 'application/json');
    }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ($this->server['SERVER_PORT'] ?? null) == 443
            || strtolower($this->header('X-Forwarded-Proto', '') ?? '') === 'https';
    }
}
