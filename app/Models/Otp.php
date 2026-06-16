<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Otp extends Model
{
    protected static string $table = 'otps';

    public static function latestForMobile(string $mobile): ?array
    {
        return Database::selectOne(
            'SELECT * FROM otps WHERE mobile = ? ORDER BY id DESC LIMIT 1',
            [$mobile]
        );
    }

    public static function store(string $mobile, string $codeHash, int $expirySeconds, string $ip): int
    {
        // Invalidate previous unverified codes for this mobile.
        Database::affecting('DELETE FROM otps WHERE mobile = ? AND verified = 0', [$mobile]);

        return Database::insert(
            'INSERT INTO otps (mobile, code_hash, ip_address, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))',
            [$mobile, $codeHash, $ip, $expirySeconds]
        );
    }

    public static function incrementAttempts(int $id): void
    {
        Database::affecting('UPDATE otps SET attempts = attempts + 1 WHERE id = ?', [$id]);
    }

    public static function markVerified(int $id): void
    {
        Database::affecting('UPDATE otps SET verified = 1 WHERE id = ?', [$id]);
    }

    public static function purgeExpired(): int
    {
        return Database::affecting('DELETE FROM otps WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}
