<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'name'           => Env::get('APP_NAME', 'Nullik Academy'),
    'env'            => Env::get('APP_ENV', 'production'),
    'debug'          => (bool) Env::get('APP_DEBUG', false),
    'url'            => rtrim((string) Env::get('APP_URL', 'http://localhost'), '/'),
    'key'            => Env::get('APP_KEY', ''),
    'timezone'       => Env::get('APP_TIMEZONE', 'Asia/Tehran'),
    'force_https'    => (bool) Env::get('FORCE_HTTPS', false),

    'session_name'   => Env::get('SESSION_NAME', 'nullik_session'),
    'session_secure' => (bool) Env::get('SESSION_SECURE', true),

    'storage_path'   => Env::get('STORAGE_PATH', 'storage'),
    'receipts_path'  => Env::get('RECEIPTS_PATH', 'storage/receipts'),
    'logs_path'      => Env::get('LOGS_PATH', 'storage/logs'),

    'support_url'    => Env::get('TELEGRAM_SUPPORT_URL', 'https://t.me/nullikacademy_support'),
];
