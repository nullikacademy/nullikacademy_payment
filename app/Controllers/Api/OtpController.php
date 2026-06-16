<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\OtpService;

final class OtpController extends Controller
{
    /** POST /api/send-otp */
    public function send(Request $request): never
    {
        $this->ensureCsrf($request);

        $data = $this->validate($request, [
            'mobile' => 'required|mobile_ir',
        ]);

        $mobile = normalize_mobile((string) $data['mobile']);
        $result = (new OtpService())->send($mobile, $request->ip());

        if (!$result['ok']) {
            Response::json([
                'success'     => false,
                'message'     => $result['message'],
                'retry_after' => $result['retry_after'] ?? 0,
            ], 429);
        }

        Session::set('otp_mobile', $mobile);

        Response::success([
            'expiry'          => $result['expiry'],
            'resend_cooldown' => $result['resend_cooldown'],
            'debug_code'      => $result['debug_code'] ?? null,
        ], $result['message']);
    }

    /** POST /api/verify-otp */
    public function verify(Request $request): never
    {
        $this->ensureCsrf($request);

        $data = $this->validate($request, [
            'mobile' => 'required|mobile_ir',
            'code'   => 'required',
        ]);

        $mobile = normalize_mobile((string) $data['mobile']);
        $result = (new OtpService())->verify($mobile, (string) $data['code']);

        if (!$result['ok']) {
            Response::json([
                'success'   => false,
                'message'   => $result['message'],
                'remaining' => $result['remaining'] ?? null,
            ], 422);
        }

        // Mark this session as having a verified mobile for checkout.
        Session::set('verified_mobile', $mobile);
        Session::set('verified_at', time());

        Response::success([], $result['message']);
    }
}
