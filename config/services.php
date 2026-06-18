<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'ippanel' => [
        'api_key'        => Env::get('IPPANEL_API_KEY', ''),
        'base_url'       => rtrim((string) Env::get('IPPANEL_BASE_URL', 'https://api.ippanel.com/v1'), '/'),
        'send_url'       => Env::get('IPPANEL_SEND_URL', 'https://edge.ippanel.com/v1/api/send'),
        'sender'         => Env::get('IPPANEL_SENDER', ''),
        'pattern_otp'    => Env::get('IPPANEL_PATTERN_OTP', ''),
        'pattern_order_user'  => Env::get('IPPANEL_PATTERN_ORDER_USER', ''),
        'pattern_order_admin' => Env::get('IPPANEL_PATTERN_ORDER_ADMIN', ''),
        'pattern_delivered'   => Env::get('IPPANEL_PATTERN_DELIVERED', ''),
        'admin_mobile'   => Env::get('ADMIN_NOTIFY_MOBILE', ''),
    ],

    'telegram' => [
        'bot_token'   => Env::get('TELEGRAM_BOT_TOKEN', ''),
        'chat_id'     => Env::get('TELEGRAM_CHAT_ID', ''),
        'support_url' => Env::get('TELEGRAM_SUPPORT_URL', 'https://t.me/nullikacademy_support'),
    ],

    'usdt' => [
        'api'       => Env::get('USDT_PRICE_API', 'https://api-web.tabdeal.org/r/plots/currencies/dynamic-info/'),
        'cache_ttl' => (int) Env::get('USDT_PRICE_CACHE_TTL', 600),
    ],

    'payment' => [
        'card_number'  => Env::get('PAYMENT_CARD_NUMBER', ''),
        'iban'         => Env::get('PAYMENT_IBAN', ''),
        'account_name' => Env::get('PAYMENT_ACCOUNT_NAME', 'مرتضی گلستانی'),
        'bank_name'    => Env::get('PAYMENT_BANK_NAME', 'بانک پاسارگاد'),
    ],

    'otp' => [
        'length'          => (int) Env::get('OTP_LENGTH', 4),
        'expiry'          => (int) Env::get('OTP_EXPIRY_SECONDS', 120),
        'max_attempts'    => (int) Env::get('OTP_MAX_ATTEMPTS', 3),
        'resend_cooldown' => (int) Env::get('OTP_RESEND_COOLDOWN', 60),
    ],

    'rate_limits' => [
        'otp_per_hour'      => (int) Env::get('RATE_LIMIT_OTP_PER_HOUR', 8),
        'login_per_15min'   => (int) Env::get('RATE_LIMIT_LOGIN_PER_15MIN', 5),
        'api_per_min'       => (int) Env::get('RATE_LIMIT_API_PER_MIN', 60),
    ],

    'upload' => [
        'max_size'      => 10 * 1024 * 1024, // 10 MB
        'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_ext'   => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];
