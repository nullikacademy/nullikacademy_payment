<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * PDO singleton wrapper. All queries use prepared statements to
 * prevent SQL injection.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $cfg = Config::get('database');
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );

        try {
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode());
        }

        return self::$pdo;
    }

    /** Run a prepared statement and return the PDOStatement. */
    public static function statement(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (associative array) or null. */
    public static function selectOne(string $sql, array $params = []): ?array
    {
        $row = self::statement($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all rows. */
    public static function select(string $sql, array $params = []): array
    {
        return self::statement($sql, $params)->fetchAll();
    }

    /** Run an INSERT and return the last insert id. */
    public static function insert(string $sql, array $params = []): int
    {
        self::statement($sql, $params);
        return (int) self::connection()->lastInsertId();
    }

    /** Run an UPDATE/DELETE and return affected rows. */
    public static function affecting(string $sql, array $params = []): int
    {
        return self::statement($sql, $params)->rowCount();
    }

    /** Fetch a single scalar value. */
    public static function scalar(string $sql, array $params = []): mixed
    {
        return self::statement($sql, $params)->fetchColumn();
    }

    public static function beginTransaction(): void
    {
        self::connection()->beginTransaction();
    }

    public static function commit(): void
    {
        self::connection()->commit();
    }

    public static function rollBack(): void
    {
        if (self::connection()->inTransaction()) {
            self::connection()->rollBack();
        }
    }
}
