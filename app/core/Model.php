<?php

class Model
{
    protected static string $table = '';

    protected static function table(): string
    {
        return self::guardTableName(static::$table);
    }

    protected static function guardTableName(string $table): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('Invalid model table name.');
        }

        return $table;
    }

    protected static function guardColumnName(string $column): string
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('Invalid model column name.');
        }

        return '`' . $column . '`';
    }

    protected static function whereClause(array $conditions, array &$bindings): string
    {
        if ($conditions === []) {
            return '';
        }

        $clauses = [];

        foreach ($conditions as $column => $value) {
            $safeColumn = self::guardColumnName((string)$column);

            if ($value === null) {
                $clauses[] = $safeColumn . ' IS NULL';
                continue;
            }

            $clauses[] = $safeColumn . ' = ?';
            $bindings[] = $value;
        }

        return ' WHERE ' . implode(' AND ', $clauses);
    }

    protected static function orderByClause(string $orderBy): string
    {
        $orderBy = trim($orderBy);
        if ($orderBy === '') {
            return '';
        }

        $parts = array_map('trim', explode(',', $orderBy));
        $safe = [];

        foreach ($parts as $part) {
            if (!preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)(?:\s+(ASC|DESC))?$/i', $part, $matches)) {
                throw new InvalidArgumentException('Invalid order by clause.');
            }

            $direction = strtoupper($matches[2] ?? 'ASC');
            $safe[] = self::guardColumnName($matches[1]) . ' ' . $direction;
        }

        return ' ORDER BY ' . implode(', ', $safe);
    }

    protected static function limitClause(int $limit): string
    {
        return $limit > 0 ? ' LIMIT ' . $limit : '';
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT * FROM `' . static::table() . '` WHERE `id` = ? LIMIT 1', [$id]);
    }

    public static function findAll(array $conditions = [], string $orderBy = 'id DESC', int $limit = 0): array
    {
        $bindings = [];
        $sql = 'SELECT * FROM `' . static::table() . '`'
            . static::whereClause($conditions, $bindings)
            . static::orderByClause($orderBy)
            . static::limitClause($limit);

        return Database::fetchAll($sql, $bindings);
    }

    public static function create(array $data): int|false
    {
        if ($data === []) {
            return false;
        }

        $columns = array_keys($data);
        $safeColumns = array_map([self::class, 'guardColumnName'], $columns);
        $placeholders = array_fill(0, count($columns), '?');

        Database::query(
            'INSERT INTO `' . static::table() . '` (' . implode(', ', $safeColumns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            array_values($data)
        );

        return (int)Database::lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = self::guardColumnName((string)$column) . ' = ?';
        }

        $bindings = array_values($data);
        $bindings[] = $id;

        $statement = Database::query(
            'UPDATE `' . static::table() . '` SET ' . implode(', ', $sets) . ' WHERE `id` = ?',
            $bindings
        );

        return $statement->rowCount() >= 0;
    }

    public static function delete(int $id): bool
    {
        $statement = Database::query('DELETE FROM `' . static::table() . '` WHERE `id` = ?', [$id]);
        return $statement->rowCount() > 0;
    }

    public static function count(array $conditions = []): int
    {
        $bindings = [];
        $row = Database::fetch(
            'SELECT COUNT(*) AS aggregate FROM `' . static::table() . '`' . static::whereClause($conditions, $bindings),
            $bindings
        );

        return (int)($row['aggregate'] ?? 0);
    }
}
