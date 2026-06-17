<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Http;
use App\Core\Logger;
use App\Models\TelegramLog;
use App\Services\Crypto;

/**
 * Telegram Bot API integration for admin order notifications.
 * Docs: https://core.telegram.org/bots/api
 */
final class TelegramService
{
    public function notifyNewOrder(array $order): bool
    {
        $cfg = Config::get('services.telegram');
        if (empty($cfg['bot_token']) || empty($cfg['chat_id'])) {
            Logger::warning('Telegram not configured; notification skipped.');
            TelegramLog::record($order['id'] ?? null, null, '', 'failed', 'not_configured');
            return false;
        }

        $message = $this->buildMessage($order);
        $url = "https://api.telegram.org/bot{$cfg['bot_token']}/sendMessage";

        $response = Http::postJson($url, [
            'chat_id'                  => $cfg['chat_id'],
            'text'                     => $message,
            'parse_mode'               => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        $ok = $response['ok'] && (($response['json']['ok'] ?? false) === true);
        TelegramLog::record(
            $order['id'] ?? null,
            (string) $cfg['chat_id'],
            $message,
            $ok ? 'sent' : 'failed',
            $response['body']
        );

        if (!$ok) {
            Logger::error('Telegram send failed.', ['status' => $response['status']]);
        }

        return $ok;
    }

    private function buildMessage(array $order): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

        $password = '—';
        if (($order['mode_type'] ?? '') === 'email_password' && !empty($order['password_enc'])) {
            $password = $esc(Crypto::decrypt($order['password_enc']));
        }

        $receiptUrl = '—';
        if (!empty($order['receipt_token'])) {
            $receiptUrl = url('admin/receipts/' . $order['receipt_token']);
        }

        $lines = [
            '🛒 <b>سفارش جدید در نالیک آکادمی</b>',
            '',
            '🔢 <b>شماره سفارش:</b> ' . $esc($order['order_number'] ?? ''),
            '👤 <b>مشتری:</b> ' . $esc(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')),
            '📱 <b>موبایل:</b> ' . $esc($order['mobile'] ?? ''),
            '🧰 <b>ابزار:</b> ' . $esc($order['tool_name'] ?? ''),
            '📦 <b>پلن:</b> ' . $esc($order['plan_name'] ?? ''),
            '💵 <b>مبلغ:</b> ' . $esc(number_format((float) ($order['price_irt'] ?? 0))) . ' تومان (' . $esc($order['price_usdt'] ?? 0) . ' USDT)',
            '📧 <b>ایمیل:</b> ' . $esc($order['email'] ?? '—'),
            '🔑 <b>رمز عبور:</b> ' . $password,
            '🏢 <b>شناسه سازمانی:</b> ' . $esc($order['organization_id'] ?? '—'),
            '🧾 <b>رسید:</b> ' . $receiptUrl,
            '📅 <b>تاریخ:</b> ' . $esc($order['created_at'] ?? date('Y-m-d H:i:s')),
        ];

        return implode("\n", $lines);
    }
}
