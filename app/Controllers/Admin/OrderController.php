<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\AdminNote;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Receipt;
use App\Models\Tool;
use App\Services\AuthService;
use App\Services\Crypto;
use App\Services\ReceiptService;

final class OrderController extends Controller
{
    private const PER_PAGE = 15;

    /** GET /admin/orders */
    public function index(Request $request): never
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'status'    => $request->query('status', ''),
            'tool_id'   => $request->query('tool_id', ''),
            'plan_id'   => $request->query('plan_id', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to'   => $request->query('date_to', ''),
            'search'    => trim((string) $request->query('search', '')),
        ];

        $result = Order::search($filters, $page, self::PER_PAGE);
        $totalPages = (int) ceil($result['total'] / self::PER_PAGE);

        $this->view('admin/orders/index', [
            'title'       => 'سفارش‌ها | مدیریت',
            'admin'       => (new AuthService())->user(),
            'orders'      => $result['items'],
            'total'       => $result['total'],
            'page'        => $page,
            'total_pages' => max(1, $totalPages),
            'filters'     => $filters,
            'tools'       => Tool::all('name ASC'),
            'statuses'    => Order::STATUSES,
        ], 'admin/layouts/admin');
    }

    /** GET /admin/orders/{id} */
    public function show(Request $request, string $id): never
    {
        $order = Order::find((int) $id);
        if (!$order) {
            Response::notFound();
        }

        // Decrypt password for the admin to provision the account.
        $order['password_plain'] = $order['mode_type'] === 'email_password'
            ? Crypto::decrypt($order['password_enc'])
            : null;

        $receipt = Receipt::firstWhere('order_id', (int) $id);

        $this->view('admin/orders/show', [
            'title'    => 'جزئیات سفارش ' . $order['order_number'],
            'admin'    => (new AuthService())->user(),
            'order'    => $order,
            'history'  => OrderStatusHistory::forOrder((int) $id),
            'notes'    => AdminNote::forOrder((int) $id),
            'receipt'  => $receipt,
            'statuses' => Order::STATUSES,
        ], 'admin/layouts/admin');
    }

    /** POST /admin/orders/{id}/status */
    public function updateStatus(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $auth = new AuthService();

        $data = $this->validate($request, [
            'status' => 'required|in:pending_payment,pending_review,approved,rejected,delivered',
        ]);

        $order = Order::find((int) $id);
        if (!$order) {
            Response::error('سفارش یافت نشد.', 404);
        }

        Order::changeStatus((int) $id, (string) $data['status'], $auth->id(), (string) $request->input('note', ''));

        // Notify the customer by SMS when the order is delivered.
        if ($data['status'] === 'delivered' && $order['status'] !== 'delivered') {
            try {
                $plan = \App\Models\Plan::find((int) $order['plan_id']);
                $duration = $plan['duration'] ?? '';
                $product = trim('اکانت ' . $duration . ' هوش مصنوعی ' . $order['tool_name'] . ' ' . $order['plan_name']);
                (new \App\Services\SmsService())->sendDeliveredToUser($order['mobile'], [
                    'name'    => $order['first_name'],
                    'product' => $product,
                ]);
            } catch (\Throwable $e) {
                \App\Core\Logger::error('Delivered SMS failed.', ['error' => $e->getMessage()]);
            }
        }

        Response::success([
            'status'       => $data['status'],
            'status_label' => Order::statusLabel((string) $data['status']),
        ], 'وضعیت سفارش به‌روزرسانی شد.');
    }

    /** DELETE /admin/orders/{id} */
    public function destroy(Request $request, string $id): never
    {
        $this->ensureCsrf($request);

        $order = Order::find((int) $id);
        if (!$order) {
            Response::error('سفارش یافت نشد.', 404);
        }

        // Remove the receipt file(s) and rows tied to this order.
        $service = new ReceiptService();
        foreach (Receipt::where('order_id', (int) $id) as $receipt) {
            $path = $service->absolutePath($receipt['file_path']);
            if (is_file($path)) {
                @unlink($path);
            }
            Receipt::delete((int) $receipt['id']);
        }

        // order_status_history and admin_notes cascade via FK.
        Order::delete((int) $id);

        \App\Core\Logger::audit('order_deleted', [
            'order_id' => (int) $id,
            'number'   => $order['order_number'] ?? '',
            'admin_id' => (new AuthService())->id(),
        ]);

        Response::success([], 'سفارش حذف شد.');
    }

    /** POST /admin/orders/{id}/notes */
    public function addNote(Request $request, string $id): never
    {
        $this->ensureCsrf($request);
        $auth = new AuthService();

        $data = $this->validate($request, [
            'note' => 'required|max:2000',
        ]);

        $order = Order::find((int) $id);
        if (!$order) {
            Response::error('سفارش یافت نشد.', 404);
        }

        $admin = $auth->user();
        $noteId = AdminNote::add(
            (int) $id,
            $auth->id(),
            $admin['full_name'] ?? $admin['username'] ?? 'ادمین',
            (string) $data['note']
        );

        Response::success([
            'id'         => $noteId,
            'note'       => e((string) $data['note']),
            'admin_name' => e($admin['full_name'] ?? $admin['username'] ?? 'ادمین'),
            'created_at' => date('Y-m-d H:i'),
        ], 'یادداشت ثبت شد.');
    }

    /** GET /admin/orders/export */
    public function export(Request $request): never
    {
        $filters = [
            'status'    => $request->query('status', ''),
            'tool_id'   => $request->query('tool_id', ''),
            'date_from' => $request->query('date_from', ''),
            'date_to'   => $request->query('date_to', ''),
            'search'    => trim((string) $request->query('search', '')),
        ];

        $orders = Order::allForExport($filters);

        if (!headers_sent()) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="orders-' . date('Ymd-His') . '.csv"');
        }

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel compatibility with Persian text.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['شماره سفارش', 'نام', 'موبایل', 'ابزار', 'پلن', 'مبلغ (تومان)', 'USDT', 'وضعیت', 'تاریخ']);
        foreach ($orders as $o) {
            fputcsv($out, [
                $o['order_number'],
                $o['first_name'] . ' ' . $o['last_name'],
                $o['mobile'],
                $o['tool_name'],
                $o['plan_name'],
                $o['price_irt'],
                $o['price_usdt'],
                Order::statusLabel($o['status']),
                $o['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }

    /** GET /admin/receipts/{token} — stream a receipt securely. */
    public function receipt(Request $request, string $token): never
    {
        $receipt = Receipt::findByToken($token);
        if (!$receipt) {
            Response::notFound();
        }

        $service = new ReceiptService();
        $path = $service->absolutePath($receipt['file_path']);
        if (!is_file($path)) {
            Response::notFound();
        }

        if (!headers_sent()) {
            header('Content-Type: ' . ($receipt['mime_type'] ?: 'application/octet-stream'));
            header('Content-Length: ' . filesize($path));
            header('Content-Disposition: inline; filename="receipt-' . $receipt['id'] . '"');
            header('Cache-Control: private, no-store');
        }
        readfile($path);
        exit;
    }
}
