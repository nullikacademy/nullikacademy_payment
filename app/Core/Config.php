<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Configuration repository. Loads php config files from /config
 * and exposes dot-notation access (e.g. config('app.name')).
 */
final class Config
{
    private static array $items = [];

    public static function load(string $configDir): void
    {
        foreach (glob(rtrim($configDir, '/') . '/*.php') as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $ref = &self::$items;
        foreach ($segments as $i => $segment) {
            if ($i === count($segments) - 1) {
                $ref[$segment] = $value;
            } else {
                if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                    $ref[$segment] = [];
                }
                $ref = &$ref[$segment];
            }
        }
    }
}
