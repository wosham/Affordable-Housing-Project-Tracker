<?php

class Migration150InternProjectWork
{
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS intern_site_entries (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                entry_date DATE NOT NULL,
                entry_title VARCHAR(180) NULL,
                work_observed TEXT NULL,
                labour_observed TEXT NULL,
                materials_observed TEXT NULL,
                equipment_observed TEXT NULL,
                weather_condition VARCHAR(180) NULL,
                issues TEXT NULL,
                safety_observations TEXT NULL,
                progress_note TEXT NULL,
                milestone_id INT UNSIGNED NULL,
                media_id INT UNSIGNED NULL,
                status ENUM('draft','submitted','reviewed','needs-correction') NOT NULL DEFAULT 'submitted',
                reviewed_by INT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                review_note TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (milestone_id) REFERENCES milestones(id) ON DELETE SET NULL,
                FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE SET NULL,
                FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_intern_entries_user_project_date (user_id, project_id, entry_date),
                INDEX idx_intern_entries_project_status (project_id, status, entry_date),
                INDEX idx_intern_entries_review (status, reviewed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS intern_site_photos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                project_id INT UNSIGNED NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                media_id INT UNSIGNED NOT NULL,
                caption VARCHAR(500) NULL,
                category ENUM('progress','material','equipment','safety','issue','general') NOT NULL DEFAULT 'general',
                latitude DECIMAL(10,8) NULL,
                longitude DECIMAL(11,8) NULL,
                status ENUM('submitted','reviewed','deleted') NOT NULL DEFAULT 'submitted',
                reviewed_by INT UNSIGNED NULL,
                reviewed_at DATETIME NULL,
                review_note TEXT NULL,
                deleted_at DATETIME NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (media_id) REFERENCES media_library(id) ON DELETE CASCADE,
                FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_intern_photos_user_project (user_id, project_id, created_at),
                INDEX idx_intern_photos_project_category (project_id, category, status),
                INDEX idx_intern_photos_media (media_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(PDO $pdo): void
    {
    }
}
