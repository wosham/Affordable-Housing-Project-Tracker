<?php

class Migration115ManagerProjectMonitoring
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS manager_project_notes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            project_id INT UNSIGNED NOT NULL,
            manager_id INT UNSIGNED NOT NULL,
            note_type VARCHAR(40) NOT NULL DEFAULT 'monitoring',
            title VARCHAR(180) NOT NULL,
            body TEXT NULL,
            severity VARCHAR(30) NOT NULL DEFAULT 'normal',
            status VARCHAR(30) NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_manager_project_notes_project_status (project_id, status),
            INDEX idx_manager_project_notes_manager_created (manager_id, created_at),
            INDEX idx_manager_project_notes_severity (severity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS manager_project_notes');
    }
}
