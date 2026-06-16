<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Session;
use App\Models\Admin;

/**
 * Admin authentication, session management and "remember me".
 */
final class AuthService
{
    private const SESSION_KEY = 'admin_id';

    public function attempt(string $username, string $password, bool $remember, string $ip): array
    {
        $admin = Admin::findByUsername($username);

        // Always run a hash to mitigate user-enumeration timing.
        $hash = $admin['password_hash'] ?? '$2y$10$usesomesillystringforsaltusesomesilly';
        if (!password_verify($password, $hash) || !$admin) {
            Logger::audit('admin_login_failed', ['username' => $username, 'ip' => $ip]);
            return ['ok' => false, 'message' => 'نام کاربری یا رمز عبور نادرست است.'];
        }

        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $admin['id']);
        Session::set('admin_role', $admin['role']);
        Session::set('admin_name', $admin['full_name'] ?: $admin['username']);

        Admin::recordLogin((int) $admin['id'], $ip);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            Admin::setRememberToken((int) $admin['id'], hash('sha256', $token));
            setcookie('nullik_remember', $token, [
                'expires'  => time() + 60 * 60 * 24 * 30,
                'path'     => '/',
                'secure'   => (bool) config('app.session_secure', true),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        Logger::audit('admin_login_success', ['admin_id' => $admin['id'], 'ip' => $ip]);
        return ['ok' => true, 'message' => 'ورود موفقیت‌آمیز بود.'];
    }

    public function check(): bool
    {
        if (Session::has(self::SESSION_KEY)) {
            return true;
        }
        return $this->loginFromRememberCookie();
    }

    public function user(): ?array
    {
        $id = Session::get(self::SESSION_KEY);
        return $id ? Admin::find((int) $id) : null;
    }

    public function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);
        return $id ? (int) $id : null;
    }

    public function isSuperAdmin(): bool
    {
        return Session::get('admin_role') === 'super_admin';
    }

    public function logout(): void
    {
        $id = $this->id();
        if ($id) {
            Admin::setRememberToken($id, null);
        }
        setcookie('nullik_remember', '', time() - 3600, '/');
        Session::destroy();
    }

    private function loginFromRememberCookie(): bool
    {
        $token = $_COOKIE['nullik_remember'] ?? '';
        if ($token === '') {
            return false;
        }
        $admin = Admin::findByRememberToken(hash('sha256', $token));
        if (!$admin) {
            return false;
        }
        Session::regenerate();
        Session::set(self::SESSION_KEY, (int) $admin['id']);
        Session::set('admin_role', $admin['role']);
        Session::set('admin_name', $admin['full_name'] ?: $admin['username']);
        return true;
    }
}
