<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Ensures the requester is an authenticated admin.
 */
final class AdminAuthMiddleware
{
    public function handle(Request $request): void
    {
        $auth = new AuthService();
        if (!$auth->check()) {
            if ($request->isAjax() || str_starts_with($request->uri(), '/api')) {
                Response::error('احراز هویت نشده‌اید.', 401);
            }
            Response::redirect(url('admin/login'));
        }
    }
}
