<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Order extends Model
{
    protected static string $table = 'orders';

    public const STATUSES = [
        'pending_payment' => 'در انتظار پرداخت',
        'pending_review'  => 'در انتظار بررسی',
        'approved'        => 'تأیید شده',
        'rejected'        => 'رد شده',
        'delivered'       => 'تحویل داده شده',
    ];

    public static function findByNumber(string $number): ?array
    {
        return self::firstWhere('order_number', $number);
    }

    public static function generateNumber(): string
    {
        do {
            $number = 'NA-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while (self::findByNumber($number) !== null);
        return $number;
    }

    /**
     * Paginated, filtered order listing for the admin panel.
     */
    public static function search(array $filters, int $page, int $perPage): array
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'o.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['tool_id'])) {
            $conditions[] = 'o.tool_id = ?';
            $params[] = (int) $filters['tool_id'];
        }
        if (!empty($filters['plan_id'])) {
            $conditions[] = 'o.plan_id = ?';
            $params[] = (int) $filters['plan_id'];
        }
        if (!empty($filters['date_from'])) {
            $conditions[] = 'o.created_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'o.created_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['search'])) {
            $conditions[] = '(o.order_number LIKE ? OR o.mobile LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ? OR o.email LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }

        $where = implode(' AND ', $conditions);
        $offset = ($page - 1) * $perPage;

        $items = Database::select(
            "SELECT o.* FROM orders o WHERE {$where} ORDER BY o.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = (int) Database::scalar("SELECT COUNT(*) FROM orders o WHERE {$where}", $params);

        return ['items' => $items, 'total' => $total];
    }

    public static function changeStatus(int $id, string $toStatus, ?int $adminId, ?string $note = null): void
    {
        $order = self::find($id);
        if (!$order) {
            return;
        }
        Database::beginTransaction();
        try {
            Database::affecting('UPDATE orders SET status = ? WHERE id = ?', [$toStatus, $id]);
            Database::insert(
                'INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, note) VALUES (?, ?, ?, ?, ?)',
                [$id, $order['status'], $toStatus, $adminId, $note]
            );
            Database::commit();
        } catch (\Throwable $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function attachReceipt(int $id, string $path): void
    {
        Database::affecting('UPDATE orders SET receipt_path = ?, status = ? WHERE id = ?', [$path, 'pending_review', $id]);
    }

    /* ---------- Dashboard statistics ---------- */

    public static function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? $status;
    }

    public static function countByStatus(string $status): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE status = ?', [$status]);
    }

    public static function countToday(): int
    {
        return (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');
    }

    public static function revenue(): int
    {
        return (int) Database::scalar(
            "SELECT COALESCE(SUM(price_irt),0) FROM orders WHERE status IN ('approved','delivered')"
        );
    }

    public static function conversionRate(): float
    {
        $total = (int) Database::scalar('SELECT COUNT(*) FROM orders');
        if ($total === 0) {
            return 0.0;
        }
        $converted = (int) Database::scalar("SELECT COUNT(*) FROM orders WHERE status IN ('approved','delivered')");
        return round(($converted / $total) * 100, 1);
    }

    public static function ordersByDay(int $days = 14): array
    {
        return Database::select(
            'SELECT DATE(created_at) AS day, COUNT(*) AS total
             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC',
            [$days]
        );
    }

    public static function ordersByTool(): array
    {
        return Database::select(
            'SELECT tool_name, COUNT(*) AS total FROM orders
             GROUP BY tool_name ORDER BY total DESC LIMIT 10'
        );
    }

    public static function allForExport(array $filters): array
    {
        $result = self::search($filters, 1, 100000);
        return $result['items'];
    }
}
