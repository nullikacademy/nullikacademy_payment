<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;

/**
 * Generic per-IP API rate limiter.
 */
final class RateLimitMiddleware
{
    public function handle(Request $request): void
    {
        $max = (int) Config::get('services.rate_limits.api_per_min', 60);
        $key = 'api:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, $max)) {
            Response::json([
                'success' => false,
                'message' => 'تعداد درخواست‌ها بیش از حد مجاز است. کمی صبر کنید.',
            ], 429, ['Retry-After' => (string) RateLimiter::availableIn($key)]);
        }

        RateLimiter::hit($key, 60);
    }
}
