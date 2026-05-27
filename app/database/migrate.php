<?php
// AHPTC Database Migration Runner
// Run from CLI: php app/database/migrate.php [up|down|status]
// Run from browser: /app/database/migrate.php?action=up (localhost only — protected)

define('RUNNING_MIGRATIONS', true);

require_once __DIR__ . '/DatabaseConfig.php';

$action = $argv[1] ?? ($_GET['action'] ?? 'up');

// Security: only run from CLI or localhost in browser
if (php_sapi_name() !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'])) {
        http_response_code(403);
        die('Migration runner only accessible from localhost.');
    }
}

// Connect
$db = DatabaseConfig::pdo();

// Ensure migrations tracking table exists
$db->exec("CREATE TABLE IF NOT EXISTS _migrations (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    migration  VARCHAR(200) NOT NULL UNIQUE,
    ran_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$migrationsPath = __DIR__ . '/migrations/';
$files = glob($migrationsPath . '*.php');
sort($files);

$ran = $db->query("SELECT migration FROM _migrations")->fetchAll(\PDO::FETCH_COLUMN);

foreach ($files as $file) {
    $name = basename($file, '.php');

    if ($action === 'up') {
        if (!in_array($name, $ran)) {
            $classes = get_declared_classes();
            require_once $file;
            $newClasses = array_diff(get_declared_classes(), $classes);
            $class = array_pop($newClasses);
            if ($class) {
                (new $class())->up($db);
                $stmt = $db->prepare("INSERT INTO _migrations (migration) VALUES (?)");
                $stmt->execute([$name]);
                echo "✓ Migrated: {$name}\n";
            }
        } else {
            echo "  Skipped (already ran): {$name}\n";
        }
    }

    if ($action === 'status') {
        $status = in_array($name, $ran) ? '[✓ ran]' : '[ pending ]';
        echo "{$status} {$name}\n";
    }
}

if ($action === 'up') { echo "\nAll migrations complete.\n"; }
