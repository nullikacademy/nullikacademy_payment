<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Receipt extends Model
{
    protected static string $table = 'receipts';

    public static function store(string $path, string $originalName, string $mime, int $size): array
    {
        $token = bin2hex(random_bytes(16));
        $id = Database::insert(
            'INSERT INTO receipts (file_path, original_name, mime_type, size_bytes, token) VALUES (?, ?, ?, ?, ?)',
            [$path, $originalName, $mime, $size, $token]
        );
        return ['id' => $id, 'token' => $token, 'path' => $path];
    }

    public static function findByToken(string $token): ?array
    {
        return self::firstWhere('token', $token);
    }

    public static function linkToOrder(int $receiptId, int $orderId): void
    {
        Database::affecting('UPDATE receipts SET order_id = ? WHERE id = ?', [$orderId, $receiptId]);
    }
}
