<?php

class DatabaseBackupService
{
    public static function create(int $userId = 0, string $origin = 'manual'): array
    {
        if (DatabaseBackup::running()) throw new RuntimeException('A database backup is already running.');
        $config = require dirname(__DIR__) . '/config/database.php';
        $directory = self::directory();
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Protected backup storage could not be created.');
        $name = preg_replace('/[^a-z0-9_-]+/i', '-', (string)($config['database'] ?? 'database')) . '-' . date('Ymd-His') . '.sql.gz';
        $target = $directory . DIRECTORY_SEPARATOR . $name;
        Database::query('INSERT INTO database_backups (filename, storage_key, status, origin, created_by, started_at) VALUES (?, ?, ?, ?, ?, NOW())', [$name, $name, 'running', $origin, $userId ?: null]);
        $id = (int)Database::lastInsertId();
        try {
            self::dump($config, $target);
            $size = (int)filesize($target);
            if ($size < 128) throw new RuntimeException('Backup output was unexpectedly small.');
            $checksum = hash_file('sha256', $target);
            if (!is_string($checksum) || $checksum === '') throw new RuntimeException('Backup checksum could not be calculated.');
            Database::query('UPDATE database_backups SET status = ?, size_bytes = ?, checksum_sha256 = ?, completed_at = NOW() WHERE id = ?', ['completed', $size, $checksum, $id]);
            Logger::log('create', 'database_backups', $id, ['filename' => $name, 'size_bytes' => $size, 'origin' => $origin]);
            self::prune();
            return Database::fetch('SELECT * FROM database_backups WHERE id = ?', [$id]) ?: [];
        } catch (Throwable $error) {
            if (is_file($target)) @unlink($target);
            Database::query('UPDATE database_backups SET status = ?, error_message = ?, completed_at = NOW() WHERE id = ?', ['failed', substr($error->getMessage(), 0, 1000), $id]);
            Logger::error('Database backup failed', ['backup_id' => $id, 'error' => $error->getMessage()]);
            throw $error;
        }
    }

    public static function path(array $backup): string
    {
        $key = basename((string)($backup['storage_key'] ?? ''));
        if ($key === '') throw new RuntimeException('Backup file reference is invalid.');
        return self::directory() . DIRECTORY_SEPARATOR . $key;
    }

    public static function directory(): string
    {
        $configured = trim((string)getenv('BACKUP_STORAGE_PATH'));
        if ($configured !== '') return rtrim($configured, "\\/") . DIRECTORY_SEPARATOR . 'database';
        return dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'backups';
    }

    private static function dump(array $config, string $target): void
    {
        $binary = trim((string)getenv('MYSQLDUMP_PATH'));
        if ($binary === '') {
            $binary = 'C:\xampp\mysql\bin\mysqldump.exe';
            if (!is_file($binary)) {
                $root = dirname(dirname(PHP_BINARY));
                if (basename($root) === 'apache') $root = dirname($root);
                $binary = $root . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
            }
        }
        if (!is_file($binary)) throw new RuntimeException('mysqldump was not found. Set MYSQLDUMP_PATH on the server.');
        $passArg = (string)($config['password'] ?? '');
        $passFlag = $passArg !== '' ? '--password=' . escapeshellarg($passArg) : '';
        $command = implode(' ', [escapeshellarg($binary), '--host=' . escapeshellarg((string)$config['host']), '--port=' . (int)$config['port'], '--user=' . escapeshellarg((string)$config['username']), $passFlag, '--single-transaction', '--skip-lock-tables', '--routines', '--triggers', '--events', '--add-drop-table', '--no-tablespaces', '--default-character-set=utf8mb4', escapeshellarg((string)$config['database'])]);
        $errorFile = $target . '.error';
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $errorFile, 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('mysqldump could not be started.');
        fclose($pipes[0]);
        $gzip = gzopen($target, 'wb9');
        if ($gzip === false) { proc_terminate($process); throw new RuntimeException('Backup file could not be opened.'); }
        while (!feof($pipes[1])) { $chunk = fread($pipes[1], 8192); if ($chunk !== false && $chunk !== '') gzwrite($gzip, $chunk); }
        fclose($pipes[1]); gzclose($gzip); $exit = proc_close($process);
        $error = is_file($errorFile) ? trim((string)file_get_contents($errorFile)) : ''; @unlink($errorFile);
        if ($exit !== 0) throw new RuntimeException('mysqldump failed' . ($error !== '' ? ': ' . substr($error, 0, 500) : '.'));
    }

    private static function prune(): void
    {
        $days = max(7, (int)(getenv('BACKUP_RETENTION_DAYS') ?: 14));
        foreach (Database::fetchAll('SELECT * FROM database_backups WHERE status = ? AND completed_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)', ['completed']) as $row) {
            $path = self::path($row); if (is_file($path)) @unlink($path);
            Database::query('UPDATE database_backups SET status = ? WHERE id = ?', ['pruned', (int)$row['id']]);
        }
    }
}
