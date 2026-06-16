<?php

declare(strict_types=1);

/**
 * REST API routes (JSON). Rate limited per IP.
 * @var \App\Core\Router $router
 */

use App\Controllers\Api\OrderController;
use App\Controllers\Api\OtpController;
use App\Controllers\Api\PriceController;
use App\Controllers\Api\ToolController;

$router->group(['prefix' => '/api', 'middleware' => ['RateLimitMiddleware']], function ($router): void {
    // Catalog
    $router->get('/tools', [ToolController::class, 'index']);
    $router->get('/tools/{slug}', [ToolController::class, 'show']);
    $router->get('/tools/{slug}/plans', [ToolController::class, 'plans']);

    // Pricing
    $router->get('/usdt-price', [PriceController::class, 'usdt']);

    // OTP / mobile verification
    $router->post('/send-otp', [OtpController::class, 'send']);
    $router->post('/verify-otp', [OtpController::class, 'verify']);

    // Checkout
    $router->post('/upload-receipt', [OrderController::class, 'uploadReceipt']);
    $router->post('/order/create', [OrderController::class, 'create']);
});
