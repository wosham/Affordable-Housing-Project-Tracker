<?php

class Migration106SystemSettings
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL UNIQUE,
                setting_group VARCHAR(80) NOT NULL,
                label VARCHAR(160) NOT NULL,
                description TEXT NULL,
                value LONGTEXT NULL,
                default_value LONGTEXT NULL,
                type ENUM('text','number','boolean','time','json','select','email','url') DEFAULT 'text',
                options_json JSON NULL,
                is_sensitive TINYINT(1) DEFAULT 0,
                is_public TINYINT(1) DEFAULT 0,
                sort_order INT UNSIGNED DEFAULT 0,
                updated_by INT UNSIGNED NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_system_settings_group_sort (setting_group, sort_order),
                INDEX idx_system_settings_public (is_public),
                INDEX idx_system_settings_updated (updated_at),
                CONSTRAINT fk_system_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_setting_revisions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL,
                old_value LONGTEXT NULL,
                new_value LONGTEXT NULL,
                changed_by INT UNSIGNED NULL,
                changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_system_setting_revisions_key_changed (setting_key, changed_at),
                INDEX idx_system_setting_revisions_user_changed (changed_by, changed_at),
                CONSTRAINT fk_setting_revisions_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $this->seed($pdo);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS system_setting_revisions;');
        $pdo->exec('DROP TABLE IF EXISTS system_settings;');
    }

    private function seed(PDO $pdo): void
    {
        require_once dirname(__DIR__, 2) . '/core/security.php';
        require_once dirname(__DIR__, 2) . '/core/Database.php';
        require_once dirname(__DIR__, 2) . '/core/Model.php';
        require_once dirname(__DIR__, 2) . '/models/SystemSetting.php';

        $ref = new ReflectionClass(Database::class);
        $property = $ref->getProperty('connection');
        $property->setAccessible(true);
        $previous = $property->getValue();
        $property->setValue(null, $pdo);
        SystemSetting::syncDefinitions();
        $property->setValue(null, $previous);
    }
}
