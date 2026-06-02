<?php

class SystemHealth extends Model
{
    protected static string $table = 'system_health_snapshots';

    public static function snapshot(bool $persist = false, int $userId = 0): array
    {
        $started = microtime(true);
        $checks = [
            'database' => self::databaseHealth(),
            'migrations' => self::migrationHealth(),
            'storage' => self::storageHealth(),
            'logs' => self::logHealth(),
            'security' => self::securityHealth(),
            'workflows' => self::workflowHealth(),
            'php' => self::phpHealth(),
        ];

        $flat = self::flattenChecks($checks);
        $critical = count(array_filter($flat, static fn (array $check): bool => $check['status'] === 'critical'));
        $warning = count(array_filter($flat, static fn (array $check): bool => $check['status'] === 'warning'));
        $score = max(0, 100 - ($critical * 12) - ($warning * 4));
        $status = $critical > 0 ? 'critical' : ($warning > 0 ? 'warning' : 'healthy');

        $snapshot = [
            'status' => $status,
            'score' => $score,
            'checked_at' => date('Y-m-d H:i:s'),
            'duration_ms' => (int)round((microtime(true) - $started) * 1000),
            'counts' => [
                'checks' => count($flat),
                'critical' => $critical,
                'warning' => $warning,
                'healthy' => count($flat) - $critical - $warning,
            ],
            'checks' => $checks,
            'recommendations' => self::recommendations($flat, $checks),
        ];

        if ($persist) {
            self::recordSnapshot($snapshot, $userId);
        }

        return $snapshot;
    }

    public static function recentSnapshots(int $limit = 8): array
    {
        try {
            return Database::fetchAll(
                "SELECT shs.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS created_by_name
                 FROM system_health_snapshots shs
                 LEFT JOIN users u ON u.id = shs.created_by
                 ORDER BY shs.created_at DESC, shs.id DESC
                 LIMIT " . max(1, min(20, $limit))
            );
        } catch (Throwable) {
            return [];
        }
    }

    public static function logTail(string $file = 'error.log', int $lines = 30): array
    {
        $allowed = ['error.log', 'app.log'];
        $file = in_array($file, $allowed, true) ? $file : 'error.log';
        $path = dirname(__DIR__) . '/storage/logs/' . $file;
        if (!is_file($path)) {
            return ['file' => $file, 'exists' => false, 'lines' => []];
        }

        $content = @file($path, FILE_IGNORE_NEW_LINES) ?: [];
        return [
            'file' => $file,
            'exists' => true,
            'size' => filesize($path) ?: 0,
            'modified_at' => date('Y-m-d H:i:s', filemtime($path) ?: time()),
            'lines' => array_slice($content, -max(1, min(200, $lines))),
        ];
    }

    public static function databaseHealth(): array
    {
        $checks = [];
        $meta = ['connected' => false, 'database' => '', 'version' => '', 'tables' => 0, 'rows' => []];

        try {
            $pdo = Database::connection();
            $meta['connected'] = true;
            $meta['database'] = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
            $meta['version'] = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
            $row = Database::fetch('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE()');
            $meta['tables'] = (int)($row['total'] ?? 0);
            $checks[] = self::check('DB connection', 'healthy', 'Database connection is available.', 'fa-database');
            $checks[] = self::check('Table count', $meta['tables'] >= 70 ? 'healthy' : 'warning', format_number($meta['tables']) . ' tables detected.', 'fa-table');

            foreach (['users', 'projects', 'ipcs', 'boq_items', 'programme_tasks', 'audit_logs', 'system_settings', 'subscribers', 'contact_submissions'] as $table) {
                $meta['rows'][$table] = self::tableCount($table);
            }

            foreach (['users', 'roles', 'projects', 'ipcs', 'audit_logs', 'system_settings'] as $table) {
                $exists = self::tableExists($table);
                $checks[] = self::check('Table: ' . $table, $exists ? 'healthy' : 'critical', $exists ? 'Present' : 'Missing critical table.', 'fa-table-cells');
            }
        } catch (Throwable $exception) {
            $checks[] = self::check('DB connection', 'critical', 'Database unavailable: ' . $exception->getMessage(), 'fa-database');
        }

        return ['meta' => $meta, 'checks' => $checks];
    }

    public static function migrationHealth(): array
    {
        $dir = dirname(__DIR__) . '/database/migrations';
        $files = array_map('basename', glob($dir . '/*.php') ?: []);
        sort($files);
        $fileNames = array_map(static fn (string $file): string => basename($file, '.php'), $files);
        $ran = [];
        $checks = [];

        try {
            $ran = Database::fetchAll('SELECT migration, ran_at FROM _migrations ORDER BY id ASC');
            $ranNames = array_map(static fn (array $row): string => (string)$row['migration'], $ran);
            $pending = array_values(array_diff($fileNames, $ranNames));
            $checks[] = self::check('Migration tracker', 'healthy', '_migrations table is available.', 'fa-code-branch');
            $checks[] = self::check('Pending migrations', $pending === [] ? 'healthy' : 'critical', $pending === [] ? 'No pending migrations.' : count($pending) . ' pending migration(s).', 'fa-code-branch');
        } catch (Throwable $exception) {
            $ranNames = [];
            $pending = $fileNames;
            $checks[] = self::check('Migration tracker', 'critical', '_migrations is unavailable: ' . $exception->getMessage(), 'fa-code-branch');
        }

        $numbers = [];
        foreach ($fileNames as $name) {
            if (preg_match('/^(\d+)/', $name, $match)) {
                $numbers[$match[1]][] = $name;
            }
        }
        $duplicates = array_filter($numbers, static fn (array $items): bool => count($items) > 1);
        $checks[] = self::check('Duplicate migration numbers', 'healthy', $duplicates === [] ? 'No duplicate numeric prefixes.' : count($duplicates) . ' historical duplicate prefix group(s) found; migration names are unique and tracked.', 'fa-copy');

        return [
            'meta' => [
                'files' => count($fileNames),
                'ran' => count($ranNames ?? []),
                'pending' => $pending ?? [],
                'duplicates' => $duplicates,
                'latest' => $ran ? end($ran) : null,
            ],
            'checks' => $checks,
        ];
    }

    public static function storageHealth(): array
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            'Logs' => $root . '/app/storage/logs',
            'Sessions' => $root . '/app/storage/sessions',
            'Cache' => $root . '/app/storage/cache',
            'Exports' => $root . '/app/storage/exports',
            'Uploads' => $root . '/uploads',
            'Secure uploads' => $root . '/secure-uploads',
            'News uploads' => $root . '/uploads/news',
            'Project uploads' => $root . '/uploads/projects',
            'Profile uploads' => $root . '/uploads/profiles',
        ];
        $checks = [];
        $items = [];

        foreach ($paths as $label => $path) {
            $exists = is_dir($path);
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);
            $stats = $exists ? self::directoryStats($path) : ['files' => 0, 'size' => 0, 'modified_at' => null];
            $status = !$exists || !$readable || !$writable ? 'critical' : ($stats['files'] > 5000 ? 'warning' : 'healthy');
            $checks[] = self::check($label, $status, $exists ? ($writable ? 'Writable' : 'Not writable') : 'Missing directory', 'fa-folder-open');
            $items[] = compact('label', 'path', 'exists', 'readable', 'writable', 'stats', 'status');
        }

        return ['meta' => ['directories' => $items], 'checks' => $checks];
    }

    public static function logHealth(): array
    {
        $logsDir = dirname(__DIR__) . '/storage/logs';
        $checks = [];
        $files = [];
        foreach (['error.log', 'app.log'] as $file) {
            $path = $logsDir . '/' . $file;
            $exists = is_file($path);
            $size = $exists ? (filesize($path) ?: 0) : 0;
            $tail = self::logTail($file, 20);
            $status = !$exists ? 'healthy' : ($size > 5 * 1024 * 1024 ? 'warning' : 'healthy');
            $checks[] = self::check($file, $status, $exists ? self::formatBytes($size) . ' · ' . count($tail['lines']) . ' tail lines' : 'No log file yet.', 'fa-file-lines');
            $files[] = ['file' => $file, 'path' => $path, 'exists' => $exists, 'size' => $size, 'tail' => $tail, 'status' => $status];
        }
        $checks[] = self::check('Log folder writable', is_writable($logsDir) ? 'healthy' : 'critical', is_writable($logsDir) ? 'Log directory is writable.' : 'Log directory is not writable.', 'fa-pen');

        return ['meta' => ['files' => $files], 'checks' => $checks];
    }

    public static function securityHealth(): array
    {
        $app = $GLOBALS['app_config'] ?? [];
        $checks = [];
        $debug = (bool)($app['debug'] ?? false);
        $env = (string)($app['env'] ?? 'local');
        $setupPath = dirname(__DIR__, 2) . '/admin/setup.php';
        $failedLogins = self::scalar("SELECT COUNT(*) AS total FROM audit_logs WHERE action = 'login_failed' AND DATE(created_at) = CURDATE()");
        $criticalAudits = self::scalar("SELECT COUNT(*) AS total FROM audit_logs WHERE severity = 'critical' AND DATE(created_at) = CURDATE()");
        $activeSuperadmins = User::activeSuperadminCount();

        $checks[] = self::check('Debug mode', $debug && $env !== 'local' ? 'critical' : 'healthy', $debug ? 'Debug is enabled for local development.' : 'Debug is disabled.', 'fa-bug');
        $checks[] = self::check('Environment', 'healthy', 'Environment: ' . $env, 'fa-server');
        $checks[] = self::check('Setup file', is_file($setupPath) ? 'critical' : 'healthy', is_file($setupPath) ? 'admin/setup.php still exists and should be removed after setup.' : 'Setup file is not present.', 'fa-screwdriver-wrench');
        $checks[] = self::check('Active superadmins', $activeSuperadmins > 0 ? 'healthy' : 'critical', format_number($activeSuperadmins) . ' active superadmin(s).', 'fa-user-shield');
        $checks[] = self::check('Failed logins today', $failedLogins > 10 ? 'critical' : ($failedLogins > 0 ? 'warning' : 'healthy'), format_number($failedLogins) . ' failed login(s) today.', 'fa-user-lock');
        $checks[] = self::check('Critical audit events today', $criticalAudits > 10 ? 'critical' : ($criticalAudits > 0 ? 'warning' : 'healthy'), format_number($criticalAudits) . ' critical event(s) today.', 'fa-triangle-exclamation');

        return ['meta' => compact('debug', 'env', 'failedLogins', 'criticalAudits', 'activeSuperadmins'), 'checks' => $checks];
    }

    public static function workflowHealth(): array
    {
        $items = [
            'IPCs awaiting approval' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status IN ('certified','endorsed')"), 'link' => 'admin/superadmin/ipcs.php?payment_readiness=approval-ready'],
            'Approved IPCs unpaid' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM ipcs WHERE status = 'approved'"), 'link' => 'admin/superadmin/financials.php'],
            'Programme tasks overdue' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM programme_tasks WHERE status NOT IN ('complete','cancelled') AND planned_end IS NOT NULL AND planned_end < CURDATE()"), 'link' => 'admin/superadmin/programme-of-works.php?delay=delayed'],
            'Milestones overdue' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM milestones WHERE target_date < CURDATE() AND status <> 'done'"), 'link' => 'admin/superadmin/projects.php'],
            'BOQ risk items' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM boq_items WHERE COALESCE(paid_qty,0) > COALESCE(certified_qty,0) OR COALESCE(certified_qty,0) > COALESCE(quantity,0)"), 'link' => 'admin/superadmin/boq.php?risk=overpaid'],
            'Geo-fail attendance today' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM attendance_records WHERE date = CURDATE() AND status = 'geo-fail'"), 'link' => 'admin/superadmin/attendance.php?status=geo-fail'],
            'Unread contact messages' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM contact_submissions WHERE is_read = 0 AND status <> 'archived'"), 'link' => 'admin/superadmin/contact-inbox.php?read_state=unread'],
            'Pending variations' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM variations WHERE status = 'pending'"), 'link' => 'admin/superadmin/approvals.php?tab=variations'],
            'Pending EOTs' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM eot_requests WHERE status = 'pending'"), 'link' => 'admin/superadmin/approvals.php?tab=eots'],
            'Severe H&S incidents' => ['count' => self::scalar("SELECT COUNT(*) AS total FROM hs_incidents WHERE severity IN ('high','critical') AND incident_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"), 'link' => 'admin/superadmin/reports.php'],
        ];

        $checks = [];
        foreach ($items as $label => $item) {
            $count = (int)$item['count'];
            $checks[] = self::check($label, 'healthy', $count > 0 ? format_number($count) . ' operational item(s) visible.' : 'Clear.', 'fa-list-check', ['link' => $item['link'], 'count' => $count]);
        }

        return ['meta' => ['items' => $items], 'checks' => $checks];
    }

    public static function phpHealth(): array
    {
        $required = ['PDO', 'pdo_mysql', 'mbstring', 'json', 'fileinfo', 'openssl', 'session'];
        $checks = [];
        foreach ($required as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = self::check($extension, $loaded ? 'healthy' : 'critical', $loaded ? 'Loaded' : 'Missing PHP extension.', 'fa-plug');
        }
        $checks[] = self::check('Timezone', date_default_timezone_get() === 'Africa/Nairobi' ? 'healthy' : 'warning', date_default_timezone_get(), 'fa-clock');
        $checks[] = self::check('PHP version', version_compare(PHP_VERSION, '8.1.0', '>=') ? 'healthy' : 'warning', PHP_VERSION, 'fa-code');

        return [
            'meta' => [
                'php_version' => PHP_VERSION,
                'timezone' => date_default_timezone_get(),
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
            'checks' => $checks,
        ];
    }

    private static function recordSnapshot(array $snapshot, int $userId): void
    {
        try {
            Database::query(
                'INSERT INTO system_health_snapshots (status, score, checks_json, db_json, storage_json, workflow_json, security_json, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $snapshot['status'],
                    $snapshot['score'],
                    json_encode($snapshot['checks'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($snapshot['checks']['database'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($snapshot['checks']['storage'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($snapshot['checks']['workflows'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    json_encode($snapshot['checks']['security'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    $userId > 0 ? $userId : null,
                ]
            );
        } catch (Throwable) {
        }
    }

    private static function check(string $label, string $status, string $message, string $icon, array $meta = []): array
    {
        return compact('label', 'status', 'message', 'icon', 'meta');
    }

    private static function flattenChecks(array $groups): array
    {
        $flat = [];
        foreach ($groups as $group => $payload) {
            foreach (($payload['checks'] ?? []) as $check) {
                $check['group'] = $group;
                $flat[] = $check;
            }
        }
        return $flat;
    }

    private static function recommendations(array $checks, array $groups): array
    {
        $recommendations = [];
        foreach ($checks as $check) {
            if ($check['status'] === 'healthy') {
                continue;
            }
            $recommendations[] = [
                'status' => $check['status'],
                'title' => $check['label'],
                'message' => $check['message'],
                'group' => $check['group'] ?? 'system',
            ];
        }
        return array_slice($recommendations, 0, 12);
    }

    private static function tableExists(string $table): bool
    {
        try {
            $row = Database::fetch('SELECT COUNT(*) AS total FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);
            return (int)($row['total'] ?? 0) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    private static function tableCount(string $table): int
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table) || !self::tableExists($table)) {
            return 0;
        }
        try {
            $row = Database::fetch("SELECT COUNT(*) AS total FROM {$table}");
            return (int)($row['total'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    private static function scalar(string $sql, array $bindings = []): int
    {
        try {
            $row = Database::fetch($sql, $bindings);
            return (int)($row['total'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    private static function directoryStats(string $path): array
    {
        $files = 0;
        $size = 0;
        $modified = 0;
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $files++;
                $size += $file->getSize();
                $modified = max($modified, $file->getMTime());
            }
        } catch (Throwable) {
        }

        return ['files' => $files, 'size' => $size, 'modified_at' => $modified > 0 ? date('Y-m-d H:i:s', $modified) : null];
    }

    public static function formatBytes(int|float $bytes): string
    {
        $bytes = max(0, (float)$bytes);
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }
        return number_format($bytes, $index === 0 ? 0 : 1) . ' ' . $units[$index];
    }
}
