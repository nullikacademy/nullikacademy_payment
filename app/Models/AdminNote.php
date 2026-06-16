<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class AdminNote extends Model
{
    protected static string $table = 'admin_notes';

    public static function forOrder(int $orderId): array
    {
        return Database::select(
            'SELECT * FROM admin_notes WHERE order_id = ? ORDER BY created_at DESC',
            [$orderId]
        );
    }

    public static function add(int $orderId, ?int $adminId, ?string $adminName, string $note): int
    {
        return Database::insert(
            'INSERT INTO admin_notes (order_id, admin_id, admin_name, note) VALUES (?, ?, ?, ?)',
            [$orderId, $adminId, $adminName, $note]
        );
    }
}
