<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Controller;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthController extends Controller
{
    /** GET /admin/login */
    public function showLogin(Request $request): never
    {
        if ((new AuthService())->check()) {
            Response::redirect(url('admin'));
        }
        // Login uses its own minimal auth layout.
        $content = $this->view->renderWithLayout('admin/login', [
            'title' => 'ورود مدیریت | نالیک آکادمی',
        ], 'admin/layouts/auth');
        Response::html($content);
    }

    /** POST /admin/login */
    public function login(Request $request): never
    {
        $this->ensureCsrf($request);

        $key = 'admin_login:' . $request->ip();
        $max = (int) Config::get('services.rate_limits.login_per_15min', 5);
        if (RateLimiter::tooManyAttempts($key, $max)) {
            Response::error('تعداد تلاش‌های ورود بیش از حد مجاز است. ' . RateLimiter::availableIn($key) . ' ثانیه صبر کنید.', 429);
        }

        $data = $this->validate($request, [
            'username' => 'required|max:60',
            'password' => 'required|max:190',
        ]);

        $remember = (bool) $request->input('remember', false);
        $result = (new AuthService())->attempt(
            (string) $data['username'],
            (string) $data['password'],
            $remember,
            $request->ip()
        );

        if (!$result['ok']) {
            RateLimiter::hit($key, 900);
            Response::error($result['message'], 401);
        }

        RateLimiter::clear($key);
        Response::success(['redirect' => url('admin')], $result['message']);
    }

    /** POST /admin/logout */
    public function logout(Request $request): never
    {
        $this->ensureCsrf($request);
        (new AuthService())->logout();
        Response::success(['redirect' => url('admin/login')], 'خروج انجام شد.');
    }
}
