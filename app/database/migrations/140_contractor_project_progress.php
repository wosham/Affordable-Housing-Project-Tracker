<?php

class Migration140ContractorProjectProgress
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_progress_updates (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id INT UNSIGNED NOT NULL,
                submitted_by INT UNSIGNED NOT NULL,
                old_progress TINYINT UNSIGNED NULL,
                new_progress TINYINT UNSIGNED NOT NULL DEFAULT 0,
                current_milestone VARCHAR(255) NULL,
                note TEXT NULL,
                photo_path VARCHAR(255) NULL,
                weather_note VARCHAR(180) NULL,
                work_summary TEXT NULL,
                blockers TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'submitted',
                reviewed_by INT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                review_note TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_progress_project_created (project_id, created_at),
                INDEX idx_progress_user_created (submitted_by, created_at),
                INDEX idx_progress_status_created (status, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(PDO $pdo): void
    {
    }
}
