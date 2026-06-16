<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Tool extends Model
{
    protected static string $table = 'tools';

    /** @return array<int,array> Active tools ordered for the carousel. */
    public static function active(): array
    {
        return Database::select(
            "SELECT * FROM tools WHERE status = 'active' ORDER BY sort_order ASC, id ASC"
        );
    }

    public static function findActiveBySlug(string $slug): ?array
    {
        return Database::selectOne(
            "SELECT * FROM tools WHERE slug = ? AND status = 'active' LIMIT 1",
            [$slug]
        );
    }

    public static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        if ($exceptId) {
            return (bool) Database::scalar(
                'SELECT COUNT(*) FROM tools WHERE slug = ? AND id <> ?',
                [$slug, $exceptId]
            );
        }
        return (bool) Database::scalar('SELECT COUNT(*) FROM tools WHERE slug = ?', [$slug]);
    }

    public static function paginate(int $page, int $perPage, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where = '1';
        $params = [];
        if ($search !== '') {
            $where = '(name LIKE ? OR slug LIKE ?)';
            $params = ["%{$search}%", "%{$search}%"];
        }
        $items = Database::select(
            "SELECT * FROM tools WHERE {$where} ORDER BY sort_order ASC, id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        $total = (int) Database::scalar("SELECT COUNT(*) FROM tools WHERE {$where}", $params);
        return ['items' => $items, 'total' => $total];
    }
}
