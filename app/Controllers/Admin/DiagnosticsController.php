<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Http;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\SmsService;
use App\Services\TelegramService;
use App\Services\UsdtPriceService;

/**
 * Admin-only diagnostics for external integrations (USDT, SMS, Telegram).
 * Accessible at /admin/diagnostics (requires admin login).
 */
final class DiagnosticsController extends Controller
{
    private static function mask(?string $v): string
    {
        $v = (string) $v;
        if ($v === '') {
            return '(خالی — تنظیم نشده)';
        }
        return strlen($v) <= 6 ? '••••' : substr($v, 0, 4) . '…' . substr($v, -2);
    }

    /** GET /admin/diagnostics  (optional ?sms=0912...) */
    public function index(Request $request): never
    {
        $ip = config('services.ippanel');
        $tg = config('services.telegram');

        // Config presence (masked).
        $configRows = [
            'IPPANEL_API_KEY'     => self::mask($ip['api_key']),
            'IPPANEL_SENDER'      => $ip['sender'] ?: '(خالی)',
            'IPPANEL_PATTERN_OTP' => $ip['pattern_otp'] ?: '(خالی)',
            'IPPANEL_BASE_URL'    => $ip['base_url'],
            'TELEGRAM_BOT_TOKEN'  => self::mask($tg['bot_token']),
            'TELEGRAM_CHAT_ID'    => $tg['chat_id'] ?: '(خالی)',
            'USDT_PRICE_API'      => (string) config('services.usdt.api'),
            'PHP_VERSION'         => PHP_VERSION,
            'curl'                => function_exists('curl_init') ? 'فعال' : 'غیرفعال!',
            'openssl'             => extension_loaded('openssl') ? 'فعال' : 'غیرفعال!',
        ];

        // USDT raw fetch.
        $resp = Http::get((string) config('services.usdt.api'));
        $usdt = [
            'status'    => $resp['status'],
            'error'     => $resp['error'],
            'length'    => strlen((string) ($resp['body'] ?? '')),
            'body'      => substr((string) ($resp['body'] ?? ''), 0, 4000),
            'parsed'    => json_encode((new UsdtPriceService())->ticker(true), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];

        // Optional SMS test.
        $smsResult = null;
        $smsMobile = trim((string) $request->query('sms', ''));
        if ($smsMobile !== '') {
            $mobile = normalize_mobile($smsMobile);
            $ok = (new SmsService())->sendOtp($mobile, '1234');
            $log = Database::selectOne('SELECT status, response FROM sms_logs ORDER BY id DESC LIMIT 1');
            $smsResult = [
                'mobile'   => $mobile,
                'returned' => $ok ? 'true (پذیرفته شد)' : 'false (ناموفق)',
                'status'   => $log['status'] ?? '—',
                'response' => substr((string) ($log['response'] ?? ''), 0, 3000),
            ];
        }

        // Optional Telegram test.
        $tgResult = null;
        if ($request->query('telegram') === '1') {
            $ok = (new TelegramService())->notifyNewOrder([
                'id' => 0, 'order_number' => 'TEST-' . date('His'), 'first_name' => 'تست', 'last_name' => 'تشخیصی',
                'mobile' => '0900', 'tool_name' => 'تست', 'plan_name' => 'تست', 'price_irt' => 0, 'price_usdt' => 0,
                'email' => '-', 'mode_type' => 'organization_id', 'organization_id' => '-', 'created_at' => date('Y-m-d H:i:s'),
            ]);
            $tgResult = $ok ? 'پیام تست تلگرام ارسال شد ✓' : 'ارسال تلگرام ناموفق بود ✗ (توکن/چت‌آیدی را بررسی کنید)';
        }

        $this->view('admin/diagnostics', [
            'title'      => 'عیب‌یابی سرویس‌ها',
            'admin'      => (new AuthService())->user(),
            'configRows' => $configRows,
            'usdt'       => $usdt,
            'smsResult'  => $smsResult,
            'smsMobile'  => $smsMobile,
            'tgResult'   => $tgResult,
        ], 'admin/layouts/admin');
    }
}
