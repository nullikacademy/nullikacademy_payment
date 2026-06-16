<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/**
 * Verifies CSRF token on state-changing requests.
 */
final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['POST', 'PUT', 'DELETE'], true)) {
            if (!Csrf::verify($request)) {
                Response::error('توکن امنیتی نامعتبر است. صفحه را تازه‌سازی کنید.', 419);
            }
        }
    }
}
