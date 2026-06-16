<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

final class Plan extends Model
{
    protected static string $table = 'plans';

    /** @return array<int,array> Active plans for a tool, ordered. */
    public static function activeForTool(int $toolId): array
    {
        return Database::select(
            "SELECT * FROM plans WHERE tool_id = ? AND status = 'active' ORDER BY sort_order ASC, id ASC",
            [$toolId]
        );
    }

    public static function findActive(int $id): ?array
    {
        return Database::selectOne(
            "SELECT * FROM plans WHERE id = ? AND status = 'active' LIMIT 1",
            [$id]
        );
    }

    /** Recalculate all IRT prices from a fresh USDT->IRT rate. */
    public static function recalculatePrices(float $usdtIrtRate): int
    {
        return Database::affecting(
            'UPDATE plans SET price_irt = ROUND(price_usdt * ?)',
            [$usdtIrtRate]
        );
    }

    public static function withTool(int $id): ?array
    {
        return Database::selectOne(
            'SELECT p.*, t.name AS tool_name, t.slug AS tool_slug
             FROM plans p JOIN tools t ON t.id = p.tool_id
             WHERE p.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function allWithTool(): array
    {
        return Database::select(
            'SELECT p.*, t.name AS tool_name FROM plans p
             JOIN tools t ON t.id = p.tool_id
             ORDER BY t.sort_order ASC, p.sort_order ASC'
        );
    }
}
