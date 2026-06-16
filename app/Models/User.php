<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByMobile(string $mobile): ?array
    {
        return self::firstWhere('mobile', $mobile);
    }

    /** Create the user if missing, mark mobile verified, return id. */
    public static function ensure(string $mobile): int
    {
        $existing = self::findByMobile($mobile);
        if ($existing) {
            Database::affecting(
                'UPDATE users SET mobile_verified_at = COALESCE(mobile_verified_at, NOW()) WHERE id = ?',
                [$existing['id']]
            );
            return (int) $existing['id'];
        }
        return Database::insert(
            'INSERT INTO users (mobile, mobile_verified_at) VALUES (?, NOW())',
            [$mobile]
        );
    }

    public static function updateProfile(int $id, string $firstName, string $lastName): void
    {
        Database::affecting(
            'UPDATE users SET first_name = ?, last_name = ? WHERE id = ?',
            [$firstName, $lastName, $id]
        );
    }

    public static function incrementOrders(int $id): void
    {
        Database::affecting('UPDATE users SET orders_count = orders_count + 1 WHERE id = ?', [$id]);
    }
}
