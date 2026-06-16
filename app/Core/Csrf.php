<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF token management using per-session tokens and timing-safe comparison.
 */
final class Csrf
{
    private const KEY = '__csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function verify(Request $request): bool
    {
        $sessionToken = $_SESSION[self::KEY] ?? '';
        if ($sessionToken === '') {
            return false;
        }

        $provided = (string) ($request->input('_token')
            ?? $request->header('X-CSRF-TOKEN')
            ?? '');

        return $provided !== '' && hash_equals($sessionToken, $provided);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
