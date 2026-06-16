<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\RateLimiter;
use App\Models\Otp;

/**
 * OTP lifecycle: generation, hashed storage, verification with
 * attempt limiting, expiry and resend cooldown.
 */
final class OtpService
{
    public function __construct(private SmsService $sms = new SmsService())
    {
    }

    /**
     * Generate + send an OTP. Returns a result array.
     */
    public function send(string $mobile, string $ip): array
    {
        $cfg = Config::get('services.otp');

        // Resend cooldown based on the latest record.
        $latest = Otp::latestForMobile($mobile);
        if ($latest && !$latest['verified']) {
            $age = time() - strtotime($latest['created_at']);
            $cooldown = (int) $cfg['resend_cooldown'];
            if ($age < $cooldown) {
                return [
                    'ok'      => false,
                    'message' => 'لطفاً تا پایان زمان شمارش معکوس صبر کنید.',
                    'retry_after' => $cooldown - $age,
                ];
            }
        }

        // Hourly per-mobile rate limit.
        $rlKey = 'otp_send:' . $mobile;
        $maxPerHour = (int) Config::get('services.rate_limits.otp_per_hour', 8);
        if (RateLimiter::tooManyAttempts($rlKey, $maxPerHour)) {
            return [
                'ok'      => false,
                'message' => 'تعداد درخواست‌های شما زیاد است. لطفاً بعداً تلاش کنید.',
                'retry_after' => RateLimiter::availableIn($rlKey),
            ];
        }
        RateLimiter::hit($rlKey, 3600);

        $length = (int) $cfg['length'];
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

        Otp::store($mobile, password_hash($code, PASSWORD_BCRYPT), (int) $cfg['expiry'], $ip);
        $this->sms->sendOtp($mobile, $code);

        $result = [
            'ok'             => true,
            'message'        => 'کد تأیید ارسال شد.',
            'expiry'         => (int) $cfg['expiry'],
            'resend_cooldown'=> (int) $cfg['resend_cooldown'],
        ];

        // In debug/dev mode expose the code to ease testing.
        if (Config::get('app.debug', false)) {
            $result['debug_code'] = $code;
        }

        return $result;
    }

    /**
     * Verify a submitted code.
     */
    public function verify(string $mobile, string $code): array
    {
        $cfg = Config::get('services.otp');
        $record = Otp::latestForMobile($mobile);

        if (!$record || $record['verified']) {
            return ['ok' => false, 'message' => 'کدی برای این شماره یافت نشد. مجدداً درخواست دهید.'];
        }

        if (strtotime($record['expires_at']) < time()) {
            return ['ok' => false, 'message' => 'کد تأیید منقضی شده است.'];
        }

        if ((int) $record['attempts'] >= (int) $cfg['max_attempts']) {
            return ['ok' => false, 'message' => 'تعداد تلاش‌های مجاز به پایان رسید. کد جدید درخواست دهید.'];
        }

        $code = to_english_digits($code);
        if (!password_verify($code, $record['code_hash'])) {
            Otp::incrementAttempts((int) $record['id']);
            $remaining = (int) $cfg['max_attempts'] - ((int) $record['attempts'] + 1);
            return [
                'ok'        => false,
                'message'   => 'کد وارد شده نادرست است.',
                'remaining' => max(0, $remaining),
            ];
        }

        Otp::markVerified((int) $record['id']);

        return ['ok' => true, 'message' => 'شماره موبایل با موفقیت تأیید شد.'];
    }
}
