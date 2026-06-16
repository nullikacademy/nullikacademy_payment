<?php

declare(strict_types=1);

/**
 * Admin panel routes.
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderController;
use App\Controllers\Admin\PlanController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ToolController;

// Public auth endpoints
$router->get('/admin/login', [AuthController::class, 'showLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);

// Authenticated admin area
$router->group(['prefix' => '/admin', 'middleware' => ['AdminAuthMiddleware']], function ($router): void {
    $router->post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    $router->get('', [DashboardController::class, 'index']);

    // Orders
    $router->get('/orders', [OrderController::class, 'index']);
    $router->get('/orders/export', [OrderController::class, 'export']);
    $router->get('/orders/{id}', [OrderController::class, 'show']);
    $router->post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    $router->post('/orders/{id}/notes', [OrderController::class, 'addNote']);
    $router->get('/receipts/{token}', [OrderController::class, 'receipt']);

    // Tools
    $router->get('/tools', [ToolController::class, 'index']);
    $router->post('/tools', [ToolController::class, 'store']);
    $router->put('/tools/{id}', [ToolController::class, 'update']);
    $router->post('/tools/{id}', [ToolController::class, 'update']); // multipart fallback
    $router->post('/tools/{id}/toggle', [ToolController::class, 'toggle']);
    $router->delete('/tools/{id}', [ToolController::class, 'destroy']);

    // Plans
    $router->get('/plans', [PlanController::class, 'index']);
    $router->post('/plans', [PlanController::class, 'store']);
    $router->put('/plans/{id}', [PlanController::class, 'update']);
    $router->delete('/plans/{id}', [PlanController::class, 'destroy']);

    // Admin users
    $router->get('/users', [AdminUserController::class, 'index']);
    $router->post('/users', [AdminUserController::class, 'store']);
    $router->put('/users/{id}', [AdminUserController::class, 'update']);
    $router->delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // Settings
    $router->get('/settings', [SettingsController::class, 'index']);
    $router->post('/settings', [SettingsController::class, 'update']);
    $router->post('/settings/refresh-price', [SettingsController::class, 'refreshPrice']);
});
