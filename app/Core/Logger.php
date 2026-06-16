<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal append-only file logger for audit trails and errors.
 */
final class Logger
{
    public static function log(string $level, string $message, array $context = []): void
    {
        $dir = base_path(Config::get('app.logs_path', 'storage/logs'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ''
        );
        @file_put_contents($dir . '/' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function audit(string $action, array $context = []): void
    {
        self::log('audit', $action, $context);
    }
}
