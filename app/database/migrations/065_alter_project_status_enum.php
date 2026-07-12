<?php
// NOTE: Two migrations share the '065_' prefix (this file and 065_subscribers_and_notifications.php).
// Do NOT rename already-run files — the _migrations table tracks filenames.
// Future migrations must use unique sequential numbers starting at 162.

class Migration_065_AlterProjectStatusEnum
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            "ALTER TABLE projects MODIFY status ENUM('planning','active','on_hold','stalled','completed','cancelled') DEFAULT 'planning'",
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec(
            "ALTER TABLE projects MODIFY status ENUM('planning','active','stalled','completed') DEFAULT 'planning'",
        );
    }
}
