<?php

declare(strict_types=1);

namespace App\Core;

/**
 * File-based fixed-window rate limiter. Works on shared hosting
 * without Redis/Memcached.
 */
final class RateLimiter
{
    private static function dir(): string
    {
        $dir = base_path(Config::get('app.logs_path', 'storage/logs') . '/../cache/ratelimit');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . sha1($key) . '.json';
    }

    /**
     * Returns true if the action is allowed (and increments the counter).
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return self::remaining($key, $maxAttempts, $decaySeconds) > 0
            ? self::hit($key, $decaySeconds) <= $maxAttempts
            : false;
    }

    public static function hit(string $key, int $decaySeconds): int
    {
        $file = self::file($key);
        $now = time();
        $data = ['count' => 0, 'reset' => $now + $decaySeconds];

        if (is_file($file)) {
            $stored = json_decode((string) file_get_contents($file), true);
            if (is_array($stored) && ($stored['reset'] ?? 0) > $now) {
                $data = $stored;
            }
        }

        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['count'];
    }

    public static function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return false;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || ($data['reset'] ?? 0) <= time()) {
            return false;
        }
        return ($data['count'] ?? 0) >= $maxAttempts;
    }

    public static function remaining(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return $maxAttempts;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || ($data['reset'] ?? 0) <= time()) {
            return $maxAttempts;
        }
        return max(0, $maxAttempts - (int) ($data['count'] ?? 0));
    }

    public static function availableIn(string $key): int
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return 0;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            return 0;
        }
        return max(0, (int) ($data['reset'] ?? 0) - time());
    }

    public static function clear(string $key): void
    {
        $file = self::file($key);
        if (is_file($file)) {
            @unlink($file);
        }
    }
}
