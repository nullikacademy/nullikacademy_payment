<?php

declare(strict_types=1);

/**
 * Diagnostics for external integrations (USDT price + IPPanel SMS + Telegram).
 *
 * Usage (cPanel Terminal / SSH, from project root):
 *   php cron/diagnose.php                 # check config + USDT fetch
 *   php cron/diagnose.php 09123456789     # also send a test OTP SMS to this number
 *
 * It prints raw API responses (secrets are masked) so issues can be pinpointed.
 */

$basePath = dirname(__DIR__);
require $basePath . '/app/Core/Autoloader.php';
App\Core\Autoloader::register();
App\Core\Env::load($basePath . '/.env');
App\Core\Config::load($basePath . '/config');
date_default_timezone_set((string) config('app.timezone', 'Asia/Tehran'));

use App\Core\Http;
use App\Services\SmsService;
use App\Services\UsdtPriceService;

function line(string $s = ''): void { fwrite(STDOUT, $s . "\n"); }
function mask(?string $v): string { $v = (string) $v; return $v === '' ? '(empty)' : substr($v, 0, 4) . '…' . substr($v, -2); }

line('================ Nullik Diagnostics ================');
line('PHP ' . PHP_VERSION . ' | curl: ' . (function_exists('curl_init') ? 'yes' : 'NO') . ' | openssl: ' . (extension_loaded('openssl') ? 'yes' : 'NO'));
line('');

/* ---------- Config presence ---------- */
$ip = config('services.ippanel');
$tg = config('services.telegram');
line('--- Config presence (.env) ---');
line('IPPANEL_API_KEY       : ' . mask($ip['api_key']));
line('IPPANEL_SENDER        : ' . ($ip['sender'] ?: '(empty)'));
line('IPPANEL_PATTERN_OTP   : ' . ($ip['pattern_otp'] ?: '(empty)'));
line('IPPANEL_BASE_URL      : ' . $ip['base_url']);
line('TELEGRAM_BOT_TOKEN    : ' . mask($tg['bot_token']));
line('TELEGRAM_CHAT_ID      : ' . ($tg['chat_id'] ?: '(empty)'));
line('USDT_PRICE_API        : ' . config('services.usdt.api'));
line('');

/* ---------- USDT raw fetch ---------- */
line('--- USDT price source (raw) ---');
$resp = Http::get((string) config('services.usdt.api'));
line('HTTP status: ' . $resp['status'] . ($resp['error'] ? ' | curl error: ' . $resp['error'] : ''));
$body = (string) ($resp['body'] ?? '');
line('Body length: ' . strlen($body) . ' bytes');
line('First 1200 chars of response:');
line(substr($body, 0, 1200));
line('');
line('Parsed ticker (what the app derives):');
line(json_encode((new UsdtPriceService())->ticker(true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
line('');

/* ---------- SMS test ---------- */
$mobile = $argv[1] ?? null;
if ($mobile) {
    line('--- IPPanel test OTP to ' . $mobile . ' ---');
    $ok = (new SmsService())->sendOtp(normalize_mobile($mobile), '1234');
    line('sendOtp() returned: ' . ($ok ? 'true (provider accepted)' : 'false (failed)'));
    $log = App\Core\Database::selectOne('SELECT status, response FROM sms_logs ORDER BY id DESC LIMIT 1');
    if ($log) {
        line('sms_logs.status   : ' . $log['status']);
        line('sms_logs.response : ' . substr((string) $log['response'], 0, 1500));
    }
} else {
    line('(Pass a mobile number to test SMS, e.g. php cron/diagnose.php 0912xxxxxxx)');
}
line('====================================================');
