<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight active-record style base model. All queries use
 * prepared statements via the Database wrapper.
 */
abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function table(): string
    {
        return static::$table;
    }

    public static function find(int $id): ?array
    {
        return Database::selectOne(
            'SELECT * FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ? LIMIT 1',
            [$id]
        );
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        return Database::select('SELECT * FROM ' . static::$table . ' ORDER BY ' . $orderBy);
    }

    public static function where(string $column, mixed $value, string $orderBy = 'id DESC'): array
    {
        return Database::select(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = ? ORDER BY {$orderBy}",
            [$value]
        );
    }

    public static function firstWhere(string $column, mixed $value): ?array
    {
        return Database::selectOne(
            'SELECT * FROM ' . static::$table . " WHERE {$column} = ? LIMIT 1",
            [$value]
        );
    }

    public static function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        return Database::insert($sql, $data);
    }

    public static function update(int $id, array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        $sets = implode(', ', array_map(fn ($c) => "{$c} = :{$c}", array_keys($data)));
        $data['__id'] = $id;
        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :__id',
            static::$table,
            $sets,
            static::$primaryKey
        );
        return Database::affecting($sql, $data);
    }

    public static function delete(int $id): int
    {
        return Database::affecting(
            'DELETE FROM ' . static::$table . ' WHERE ' . static::$primaryKey . ' = ?',
            [$id]
        );
    }

    public static function count(string $where = '1', array $params = []): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM ' . static::$table . " WHERE {$where}",
            $params
        );
    }
}
