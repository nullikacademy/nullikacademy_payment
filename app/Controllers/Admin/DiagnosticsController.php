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

        // Resolve effective values (admin settings override .env).
        $resolve = static fn (string $k, string $fb): string => (string) (\App\Models\Setting::get($k, null) ?: config($fb, ''));

        // Config presence (masked) + resolved SMS routing.
        $configRows = [
            'IPPANEL_API_KEY'     => self::mask($ip['api_key']),
            'IPPANEL_SENDER'      => $ip['sender'] ?: '(خالی)',
            'IPPANEL_BASE_URL'    => $ip['base_url'],
            'پترن OTP (مؤثر)'        => $resolve('sms_pattern_otp', 'services.ippanel.pattern_otp') ?: '(خالی!)',
            'پترن سفارش-مشتری (مؤثر)' => $resolve('sms_pattern_order_user', 'services.ippanel.pattern_order_user') ?: '(خالی!)',
            'پترن سفارش-ادمین (مؤثر)' => $resolve('sms_pattern_order_admin', 'services.ippanel.pattern_order_admin') ?: '(خالی!)',
            'پترن تحویل (مؤثر)'      => $resolve('sms_pattern_delivered', 'services.ippanel.pattern_delivered') ?: '(خالی!)',
            'موبایل ادمین (مؤثر)'    => $resolve('sms_admin_mobile', 'services.ippanel.admin_mobile') ?: '(خالی! - پیامک ادمین ارسال نمی‌شود)',
            'TELEGRAM_BOT_TOKEN'  => self::mask($tg['bot_token']),
            'TELEGRAM_CHAT_ID'    => $tg['chat_id'] ?: '(خالی)',
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

        // Optional admin order-SMS test (uses the admin pattern + admin mobile).
        if ($request->query('adminsms') === '1') {
            $ok = (new SmsService())->notifyAdminNewOrder([
                'name'  => 'تست تشخیصی',
                'tool'  => 'Claude',
                'plan'  => 'Pro',
                'price' => '15,062,400',
                'date'  => jalali_date(),
            ]);
            $log = Database::selectOne("SELECT status, response FROM sms_logs WHERE type='order_admin' ORDER BY id DESC LIMIT 1");
            $smsResult = [
                'mobile'   => $resolve('sms_admin_mobile', 'services.ippanel.admin_mobile') ?: '(موبایل ادمین تنظیم نشده)',
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

        // Optional: probe all known IPPanel endpoint variants.
        $probe = null;
        $probeMobile = trim((string) $request->query('probe', ''));
        if ($probeMobile !== '') {
            $probe = $this->probeIppanel(normalize_mobile($probeMobile));
        }

        // Recent error log (last lines of today's log file).
        $logTail = $this->recentLog();

        // Check whether tools.color column exists (common upgrade gotcha).
        $colorColumn = 'نامشخص';
        try {
            $col = Database::selectOne("SHOW COLUMNS FROM tools LIKE 'color'");
            $colorColumn = $col ? 'موجود است ✓' : 'وجود ندارد ✗ — باید ALTER TABLE اجرا شود';
        } catch (\Throwable $e) {
            $colorColumn = 'خطا در بررسی: ' . $e->getMessage();
        }

        $this->view('admin/diagnostics', [
            'title'      => 'عیب‌یابی سرویس‌ها',
            'admin'      => (new AuthService())->user(),
            'logTail'    => $logTail,
            'colorColumn'=> $colorColumn,
            'configRows' => $configRows,
            'usdt'       => $usdt,
            'smsResult'  => $smsResult,
            'smsMobile'  => $smsMobile,
            'tgResult'   => $tgResult,
            'probe'      => $probe,
            'probeMobile'=> $probeMobile,
        ], 'admin/layouts/admin');
    }

    /** Return the last lines of the most recent log file. */
    private function recentLog(int $lines = 40): string
    {
        $dir = base_path((string) config('app.logs_path', 'storage/logs'));
        $files = glob($dir . '/*.log');
        if (!$files) {
            return '(لاگی ثبت نشده است)';
        }
        usort($files, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $content = (string) file_get_contents($files[0]);
        $all = explode("\n", trim($content));
        return implode("\n", array_slice($all, -$lines));
    }

    /**
     * Try every known IPPanel API variant and report status + body for each,
     * so we can identify the exact endpoint/auth/payload this account uses.
     */
    private function probeIppanel(string $mobile): array
    {
        $ip = config('services.ippanel');
        $key = (string) $ip['api_key'];
        $sender = (string) $ip['sender'];
        $code = (string) $ip['pattern_otp'];
        $recipient = mobile_to_e164($mobile);
        $vars = ['code' => '1234', 'otp' => '1234'];

        $candidates = [
            [
                'name'    => 'A) api.ippanel.com/v1 — AccessKey',
                'url'     => 'https://api.ippanel.com/v1/sms/pattern/normal/send',
                'headers' => ['Authorization' => 'AccessKey ' . $key],
                'payload' => ['code' => $code, 'sender' => $sender, 'recipient' => $recipient, 'variable' => (object) $vars],
            ],
            [
                'name'    => 'B) edge.ippanel.com/v1/api/send — AccessKey',
                'url'     => 'https://edge.ippanel.com/v1/api/send',
                'headers' => ['Authorization' => 'AccessKey ' . $key],
                'payload' => ['sending_type' => 'pattern', 'from_number' => $sender, 'code' => $code, 'recipients' => [$recipient], 'params' => (object) $vars],
            ],
            [
                'name'    => 'C) rest.ippanel.com/v1 — apikey header',
                'url'     => 'https://rest.ippanel.com/v1/messages/patterns/send',
                'headers' => ['apikey' => $key],
                'payload' => ['pattern_code' => $code, 'originator' => $sender, 'recipient' => $recipient, 'values' => (object) $vars],
            ],
            [
                'name'    => 'D) api.ippanel.com/v1/messages/patterns/send — apikey',
                'url'     => 'https://api.ippanel.com/v1/messages/patterns/send',
                'headers' => ['apikey' => $key],
                'payload' => ['pattern_code' => $code, 'originator' => $sender, 'recipient' => $recipient, 'values' => (object) $vars],
            ],
            [
                'name'    => 'E) api2.ippanel.com/v1 — AccessKey',
                'url'     => 'https://api2.ippanel.com/v1/sms/pattern/normal/send',
                'headers' => ['Authorization' => 'AccessKey ' . $key],
                'payload' => ['code' => $code, 'sender' => $sender, 'recipient' => $recipient, 'variable' => (object) $vars],
            ],
            [
                'name'    => 'F) edge.ippanel.com/v1/api/send — Authorization KEY (no scheme)',
                'url'     => 'https://edge.ippanel.com/v1/api/send',
                'headers' => ['Authorization' => $key],
                'payload' => ['sending_type' => 'pattern', 'from_number' => $sender, 'code' => $code, 'recipients' => [$recipient], 'params' => (object) $vars],
            ],
        ];

        $results = [];
        foreach ($candidates as $c) {
            $resp = Http::postJson($c['url'], $c['payload'], $c['headers'], 12);
            $results[] = [
                'name'   => $c['name'],
                'url'    => $c['url'],
                'status' => $resp['status'],
                'curl'   => $resp['error'],
                'body'   => substr((string) ($resp['body'] ?? ''), 0, 600),
            ];
        }
        return $results;
    }
}
