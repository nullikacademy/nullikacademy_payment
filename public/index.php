<?php

declare(strict_types=1);

/**
 * Nullik Academy — Front Controller
 * All HTTP requests are routed through this single entry point.
 */

define('NULLIK_START', microtime(true));

$basePath = dirname(__DIR__);

// Prefer Composer autoloader; fall back to bundled PSR-4 autoloader.
$composer = $basePath . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    require $basePath . '/app/Core/Autoloader.php';
    App\Core\Autoloader::register();
}

use App\Core\App;

$app = new App($basePath);

$app->loadRoutes([
    $basePath . '/routes/web.php',
    $basePath . '/routes/api.php',
    $basePath . '/routes/admin.php',
]);

$app->run();
