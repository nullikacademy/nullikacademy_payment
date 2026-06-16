<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 autoloader fallback used when Composer's vendor/autoload.php
 * is not available (e.g. minimal shared hosting deploys).
 */
final class Autoloader
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'App\\';
            $baseDir = dirname(__DIR__) . '/';

            if (!str_starts_with($class, $prefix)) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

            if (is_file($file)) {
                require $file;
            }
        });

        // Load global helper functions.
        require dirname(__DIR__) . '/Helpers/functions.php';
    }
}
