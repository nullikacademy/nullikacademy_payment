<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class SmsLog extends Model
{
    protected static string $table = 'sms_logs';

    public static function record(string $mobile, string $type, ?string $pattern, array $payload, string $status, ?string $response): int
    {
        return Database::insert(
            'INSERT INTO sms_logs (mobile, pattern_code, type, payload, status, response) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $mobile,
                $pattern,
                $type,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                $status,
                $response ? substr($response, 0, 2000) : null,
            ]
        );
    }
}
