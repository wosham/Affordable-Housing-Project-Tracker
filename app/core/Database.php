<?php

class Database
{
    private static ?PDO $connection = null;

    public static function connection(?array $config = null): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config ??= require dirname(__DIR__) . '/config/database.php';
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'] ?? '',
            $config['password'] ?? '',
            $config['options'] ?? []
        );

        return self::$connection;
    }

    public static function query(string $sql, array $bindings = []): PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($bindings);
        return $statement;
    }

    public static function fetch(string $sql, array $bindings = []): ?array
    {
        $row = self::query($sql, $bindings)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $bindings = []): array
    {
        return self::query($sql, $bindings)->fetchAll();
    }

    public static function lastInsertId(?string $name = null): string
    {
        return self::connection()->lastInsertId($name);
    }

    public static function beginTransaction(): bool
    {
        return self::connection()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connection()->commit();
    }

    public static function rollBack(): bool
    {
        $connection = self::connection();

        if (!$connection->inTransaction()) {
            return false;
        }

        return $connection->rollBack();
    }

    public static function inTransaction(): bool
    {
        return self::connection()->inTransaction();
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
