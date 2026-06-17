<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Env;

if (!function_exists('site_text')) {
    /**
     * Editable site text. Reads the value stored by the admin "متن‌ها"
     * section (settings key text_{key}), falling back to the default.
     */
    function site_text(string $key, string $default = ''): string
    {
        $value = \App\Models\Setting::get('text_' . $key, null);
        return ($value === null || $value === '') ? $default : (string) $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        return $path === '' ? $root : $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('e')) {
    /** HTML-escape a string to prevent XSS. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.url', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['__old'][$key] ?? $default;
    }
}

if (!function_exists('money_irt')) {
    /** Format an IRT (Toman) amount with Persian thousands separators. */
    function money_irt(int|float $amount): string
    {
        return to_persian_digits(number_format((float) $amount, 0, '.', '،'));
    }
}

if (!function_exists('to_persian_digits')) {
    function to_persian_digits(string $value): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace($en, $fa, $value);
    }
}

if (!function_exists('to_english_digits')) {
    function to_english_digits(string $value): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $ar = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace(array_merge($fa, $ar), array_merge($en, $en), $value);
    }
}

if (!function_exists('normalize_mobile')) {
    /** Normalize an Iranian mobile number to 09xxxxxxxxx format. */
    function normalize_mobile(string $mobile): string
    {
        $mobile = to_english_digits(trim($mobile));
        $mobile = preg_replace('/\D/', '', $mobile) ?? '';
        if (str_starts_with($mobile, '98')) {
            $mobile = '0' . substr($mobile, 2);
        } elseif (str_starts_with($mobile, '9') && strlen($mobile) === 10) {
            $mobile = '0' . $mobile;
        }
        return $mobile;
    }
}

if (!function_exists('mobile_to_e164')) {
    /** Convert 09xxxxxxxxx to +989xxxxxxxxx (IPPanel format). */
    function mobile_to_e164(string $mobile): string
    {
        $mobile = normalize_mobile($mobile);
        return '+98' . substr($mobile, 1);
    }
}
