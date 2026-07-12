<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Forbidden'); }
define('APP_SKIP_SESSION', true);
require_once dirname(__DIR__) . '/app/core/bootstrap.php';
try { $backup = DatabaseBackupService::create(0, 'scheduled'); echo '[' . date('c') . '] Backup completed: ' . ($backup['filename'] ?? '-') . PHP_EOL; exit(0); }
catch (Throwable $error) { echo '[' . date('c') . '] Backup failed: ' . $error->getMessage() . PHP_EOL; exit(1); }
