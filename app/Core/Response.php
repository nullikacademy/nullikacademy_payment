<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response helper. Supports JSON and HTML/view responses.
 */
final class Response
{
    public static function json(array $data, int $status = 200, array $headers = []): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            foreach ($headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(array $data = [], string $message = '', int $status = 200): never
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        self::json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function html(string $content, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo $content;
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Location: ' . $url);
        }
        exit;
    }

    public static function notFound(string $message = 'یافت نشد'): never
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            self::error($message, 404);
        }
        http_response_code(404);
        $viewFile = dirname(__DIR__, 2) . '/views/pages/errors/404.php';
        if (is_file($viewFile)) {
            $content = (new View())->render('pages/errors/404', ['title' => '۴۰۴ - صفحه یافت نشد']);
            self::html($content, 404);
        }
        self::html('<h1>404 - یافت نشد</h1>', 404);
    }
}
