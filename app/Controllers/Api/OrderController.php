<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Plan;
use App\Services\OrderService;
use App\Services\ReceiptService;

final class OrderController extends Controller
{
    /** POST /api/upload-receipt */
    public function uploadReceipt(Request $request): never
    {
        $this->ensureCsrf($request);

        $this->ensureVerifiedMobile();

        $file = $request->file('receipt');
        if (!$file) {
            Response::error('فایلی انتخاب نشده است.', 422);
        }

        $result = (new ReceiptService())->store($file);
        if (!$result['ok']) {
            Response::error($result['message'], 422);
        }

        Response::success(['token' => $result['token']], $result['message']);
    }

    /** POST /api/order/create */
    public function create(Request $request): never
    {
        $this->ensureCsrf($request);
        $verifiedMobile = $this->ensureVerifiedMobile();

        // Base validation; account fields validated per plan mode below.
        $data = $this->validate($request, [
            'plan_id'    => 'required|integer',
            'first_name' => 'required|persian|max:80',
            'last_name'  => 'required|persian|max:80',
            'mobile'     => 'required|mobile_ir',
        ]);

        // Bind the mobile to the verified session value (anti-tamper).
        if (normalize_mobile((string) $data['mobile']) !== $verifiedMobile) {
            Response::error('شماره موبایل با شماره تأییدشده مطابقت ندارد.', 422);
        }

        $plan = Plan::findActive((int) $data['plan_id']);
        if (!$plan) {
            Response::error('پلن انتخابی معتبر نیست.', 422);
        }

        // Mode-specific account validation.
        if ($plan['mode_type'] === 'email_password') {
            $account = $this->validate($request, [
                'email'    => 'required|email|max:190',
                'password' => 'required|min:4|max:190',
            ]);
        } else {
            $account = $this->validate($request, [
                'email'           => 'required|email|max:190',
                'organization_id' => 'required|max:190',
            ]);
        }

        $payload = array_merge($data, $account, [
            'tool_id'       => (int) $plan['tool_id'],
            'receipt_token' => $request->input('receipt_token'),
        ]);

        $result = (new OrderService())->create($payload, $request->ip(), $request->userAgent());
        if (!$result['ok']) {
            Response::error($result['message'], 422);
        }

        // Clear checkout session state.
        Session::forget('verified_mobile');
        Session::forget('otp_mobile');

        Response::success(['order' => $result['order']], $result['message']);
    }

    private function ensureVerifiedMobile(): string
    {
        $mobile = Session::get('verified_mobile');
        $verifiedAt = (int) Session::get('verified_at', 0);

        // Verification valid for 30 minutes.
        if (!$mobile || (time() - $verifiedAt) > 1800) {
            Response::error('ابتدا شماره موبایل خود را تأیید کنید.', 403);
        }
        return (string) $mobile;
    }
}
