<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Http;
use App\Core\Logger;
use App\Models\SmsLog;

/**
 * IPPanel Pattern API integration.
 * Docs: https://docs.ippanel.com/docs/send/pattern
 */
final class SmsService
{
    /** Send an OTP code via the configured pattern. */
    public function sendOtp(string $mobile, string $code): bool
    {
        $cfg = Config::get('services.ippanel');
        return $this->sendPattern(
            $mobile,
            $cfg['pattern_otp'] ?? '',
            ['code' => $code, 'otp' => $code],
            'otp'
        );
    }

    public function sendOrderConfirmationToUser(string $mobile, array $vars): bool
    {
        $cfg = Config::get('services.ippanel');
        return $this->sendPattern($mobile, $cfg['pattern_order_user'] ?? '', $vars, 'order_user');
    }

    public function notifyAdminNewOrder(array $vars): bool
    {
        $cfg = Config::get('services.ippanel');
        $adminMobile = $cfg['admin_mobile'] ?? '';
        if ($adminMobile === '') {
            return false;
        }
        return $this->sendPattern($adminMobile, $cfg['pattern_order_admin'] ?? '', $vars, 'order_admin');
    }

    /**
     * Core pattern sender. Returns true on success, logs every attempt.
     */
    private function sendPattern(string $mobile, string $patternCode, array $variables, string $type): bool
    {
        $cfg = Config::get('services.ippanel');
        $recipient = mobile_to_e164($mobile);

        if (empty($cfg['api_key']) || $patternCode === '') {
            Logger::warning('IPPanel not configured; SMS skipped.', ['type' => $type, 'mobile' => $recipient]);
            SmsLog::record($mobile, $type, $patternCode, $variables, 'failed', 'not_configured');
            return false;
        }

        $payload = [
            'code'      => $patternCode,
            'sender'    => $cfg['sender'],
            'recipient' => $recipient,
            'variable'  => (object) $variables,
        ];

        $response = Http::postJson(
            $cfg['base_url'] . '/sms/pattern/normal/send',
            $payload,
            ['Authorization' => 'AccessKey ' . $cfg['api_key']]
        );

        $ok = $response['ok'] && (($response['json']['status'] ?? '') !== 'ERROR');
        SmsLog::record($mobile, $type, $patternCode, $variables, $ok ? 'sent' : 'failed', $response['body']);

        if (!$ok) {
            Logger::error('IPPanel send failed.', ['type' => $type, 'status' => $response['status']]);
        }

        return $ok;
    }
}
