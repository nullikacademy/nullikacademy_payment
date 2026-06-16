<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class TelegramLog extends Model
{
    protected static string $table = 'telegram_logs';

    public static function record(?int $orderId, ?string $chatId, string $message, string $status, ?string $response): int
    {
        return Database::insert(
            'INSERT INTO telegram_logs (order_id, chat_id, message, status, response) VALUES (?, ?, ?, ?, ?)',
            [$orderId, $chatId, $message, $status, $response ? substr($response, 0, 2000) : null]
        );
    }
}
