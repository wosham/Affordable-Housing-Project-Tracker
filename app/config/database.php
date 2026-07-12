<?php

/**
 * Database configuration.
 * All values are read from environment variables (set in .env).
 * Fallbacks to local dev defaults only — never commit real credentials here.
 */
return [
    "driver" => "mysql",
    "host" => (string) (getenv("DB_HOST") ?: "127.0.0.1"),
    "port" => (int) (getenv("DB_PORT") ?: 3306),
    "database" =>
        (string) (getenv("DB_DATABASE") ?: "trans_nzoia_affordable_housing"),
    "username" => (string) (getenv("DB_USERNAME") ?: "root"),
    "password" => (string) (getenv("DB_PASSWORD") ?: ""),
    "charset" => "utf8mb4",
    "collation" => "utf8mb4_unicode_ci",
    "options" => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
