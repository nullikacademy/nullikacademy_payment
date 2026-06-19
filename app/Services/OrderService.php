<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Receipt;
use App\Models\Tool;
use App\Models\User;

/**
 * Orchestrates order creation: validation, pricing, persistence,
 * notifications. Receipt is attached after upload.
 */
final class OrderService
{
    public function __construct(
        private SmsService $sms = new SmsService(),
        private TelegramService $telegram = new TelegramService(),
    ) {
    }

    /**
     * @param array $data Already-validated checkout payload.
     * @return array{ok:bool,message:string,order?:array}
     */
    public function create(array $data, string $ip, string $userAgent): array
    {
        $tool = Tool::findActiveBySlug($data['tool_slug'] ?? '') ?? Tool::find((int) ($data['tool_id'] ?? 0));
        $plan = Plan::findActive((int) ($data['plan_id'] ?? 0));

        if (!$tool || !$plan || (int) $plan['tool_id'] !== (int) $tool['id']) {
            return ['ok' => false, 'message' => 'ابزار یا پلن انتخابی معتبر نیست.'];
        }

        // Server-side price authority — never trust client prices.
        $priceUsdt = (float) $plan['price_usdt'];
        $priceIrt = (int) $plan['price_irt'];
        $mode = $plan['mode_type'];

        // Mode-specific account fields.
        $email = null;
        $passwordEnc = null;
        $organizationId = null;

        if ($mode === 'email_password') {
            if (empty($data['email']) || empty($data['password'])) {
                return ['ok' => false, 'message' => 'ایمیل و رمز عبور الزامی است.'];
            }
            $email = (string) $data['email'];
            $passwordEnc = Crypto::encrypt((string) $data['password']);
        } else { // organization_id
            if (empty($data['email']) || empty($data['organization_id'])) {
                return ['ok' => false, 'message' => 'ایمیل و شناسه سازمانی الزامی است.'];
            }
            $email = (string) $data['email'];
            $organizationId = (string) $data['organization_id'];
        }

        $mobile = normalize_mobile((string) $data['mobile']);

        Database::beginTransaction();
        try {
            $userId = User::ensure($mobile);
            User::updateProfile($userId, (string) $data['first_name'], (string) $data['last_name']);

            $orderNumber = Order::generateNumber();
            $orderId = Order::create([
                'order_number'    => $orderNumber,
                'user_id'         => $userId,
                'mobile'          => $mobile,
                'first_name'      => (string) $data['first_name'],
                'last_name'       => (string) $data['last_name'],
                'tool_id'         => (int) $tool['id'],
                'plan_id'         => (int) $plan['id'],
                'tool_name'       => $tool['name'],
                'plan_name'       => $plan['name'],
                'mode_type'       => $mode,
                'email'           => $email,
                'password_enc'    => $passwordEnc,
                'organization_id' => $organizationId,
                'price_usdt'      => $priceUsdt,
                'price_irt'       => $priceIrt,
                'payment_method'  => 'card_to_card',
                'status'          => 'pending_payment',
                'ip_address'      => $ip,
                'user_agent'      => $userAgent,
            ]);

            // Attach receipt if provided during this submission.
            $receiptToken = $data['receipt_token'] ?? null;
            if ($receiptToken) {
                $receipt = Receipt::findByToken($receiptToken);
                if ($receipt) {
                    Receipt::linkToOrder((int) $receipt['id'], $orderId);
                    Order::attachReceipt($orderId, $receipt['file_path']);
                }
            }

            User::incrementOrders($userId);

            Database::insert(
                'INSERT INTO order_status_history (order_id, from_status, to_status, note) VALUES (?, ?, ?, ?)',
                [$orderId, null, $receiptToken ? 'pending_review' : 'pending_payment', 'ثبت سفارش توسط مشتری']
            );

            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            Logger::error('Order creation failed.', ['error' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'ثبت سفارش با خطا مواجه شد. لطفاً مجدداً تلاش کنید.'];
        }

        $order = Order::find($orderId);
        // Hydrate receipt token for Telegram link.
        if (!empty($order['receipt_path'])) {
            $rec = Receipt::firstWhere('order_id', $orderId);
            $order['receipt_token'] = $rec['token'] ?? null;
        }

        // Fire notifications (failures are logged, not fatal).
        $this->dispatchNotifications($order);

        Logger::audit('order_created', ['order_id' => $orderId, 'number' => $orderNumber]);

        return [
            'ok'      => true,
            'message' => 'سفارش با موفقیت ثبت شد.',
            'order'   => [
                'order_number' => $order['order_number'],
                'status'       => $order['status'],
            ],
        ];
    }

    private function dispatchNotifications(array $order): void
    {
        try {
            $this->telegram->notifyNewOrder($order);
        } catch (\Throwable $e) {
            Logger::error('Telegram notify failed.', ['error' => $e->getMessage()]);
        }

        try {
            $plan = Plan::find((int) $order['plan_id']);
            $duration = $plan['duration'] ?? '';
            $product = trim('اکانت ' . $duration . ' هوش مصنوعی ' . $order['tool_name'] . ' ' . $order['plan_name']);

            // Customer confirmation: %name%, %product%
            $this->sms->sendOrderConfirmationToUser($order['mobile'], [
                'name'    => $order['first_name'],
                'product' => $product,
            ]);

            // Admin notification: %name%, %tool%, %plan%, %price%, %date%
            $this->sms->notifyAdminNewOrder([
                'name'  => trim($order['first_name'] . ' ' . $order['last_name']),
                'tool'  => $order['tool_name'],
                'plan'  => $order['plan_name'],
                'price' => number_format((float) $order['price_irt']),
                'date'  => jalali_date(strtotime($order['created_at'] ?? 'now')),
            ]);
        } catch (\Throwable $e) {
            Logger::error('SMS notify failed.', ['error' => $e->getMessage()]);
        }
    }
}
