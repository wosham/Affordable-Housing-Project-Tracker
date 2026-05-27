<?php

final class DatabaseConfig
{
    public static function load(): array
    {
        $config = require __DIR__ . '/../config/database.php';

        if (isset($config['database'])) {
            return [
                'host' => $config['host'] ?? '127.0.0.1',
                'port' => (int)($config['port'] ?? 3306),
                'dbname' => $config['database'],
                'user' => $config['username'] ?? 'root',
                'pass' => $config['password'] ?? '',
                'charset' => $config['charset'] ?? 'utf8mb4',
            ];
        }

        if (isset($_db_config)) {
            return $_db_config;
        }

        throw new RuntimeException('Database configuration could not be loaded.');
    }

    public static function pdo(): PDO
    {
        $db = self::load();
        $port = isset($db['port']) ? ';port=' . (int)$db['port'] : '';
        $charset = $db['charset'] ?? 'utf8mb4';

        return new PDO(
            "mysql:host={$db['host']}{$port};dbname={$db['dbname']};charset={$charset}",
            $db['user'],
            $db['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
