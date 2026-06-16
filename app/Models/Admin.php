<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Admin extends Model
{
    protected static string $table = 'admins';

    public static function findByUsername(string $username): ?array
    {
        return Database::selectOne(
            "SELECT * FROM admins WHERE username = ? AND status = 'active' LIMIT 1",
            [$username]
        );
    }

    public static function findByRememberToken(string $token): ?array
    {
        return Database::selectOne(
            "SELECT * FROM admins WHERE remember_token = ? AND status = 'active' LIMIT 1",
            [$token]
        );
    }

    public static function recordLogin(int $id, string $ip): void
    {
        Database::affecting('UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?', [$ip, $id]);
    }

    public static function setRememberToken(int $id, ?string $token): void
    {
        Database::affecting('UPDATE admins SET remember_token = ? WHERE id = ?', [$token, $id]);
    }

    public static function usernameExists(string $username, ?int $exceptId = null): bool
    {
        if ($exceptId) {
            return (bool) Database::scalar('SELECT COUNT(*) FROM admins WHERE username = ? AND id <> ?', [$username, $exceptId]);
        }
        return (bool) Database::scalar('SELECT COUNT(*) FROM admins WHERE username = ?', [$username]);
    }
}
