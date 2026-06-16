<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Hardened session wrapper.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = (bool) Config::get('app.session_secure', true);
        session_name(Config::get('app.session_name', 'nullik_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');

        session_start();

        // Rotate id periodically to prevent fixation.
        if (!isset($_SESSION['__created'])) {
            $_SESSION['__created'] = time();
        } elseif (time() - $_SESSION['__created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['__created'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['__created'] = time();
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['__flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['__flash'][$key] ?? null;
        unset($_SESSION['__flash'][$key]);
        return $val;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
