<?php

declare(strict_types=1);

/**
 * Public web routes.
 * @var \App\Core\Router $router
 */

use App\Controllers\HomeController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/success/{number}', [HomeController::class, 'success']);
