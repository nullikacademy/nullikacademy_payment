<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Setting extends Model
{
    protected static string $table = 'settings';
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }
        $row = Database::selectOne('SELECT `value` FROM settings WHERE `key` = ? LIMIT 1', [$key]);
        $value = $row['value'] ?? $default;
        self::$cache[$key] = $value;
        return $value;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        Database::statement(
            'INSERT INTO settings (`key`, `value`, `group`) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, (string) $value, $group]
        );
        self::$cache[$key] = $value;
    }

    public static function all(): array
    {
        $rows = Database::select('SELECT * FROM settings ORDER BY `group`, `key`');
        $out = [];
        foreach ($rows as $row) {
            $out[$row['key']] = $row['value'];
        }
        return $out;
    }
}
