<?php

class Migration110EmailLogs
{
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                provider VARCHAR(40) DEFAULT 'resend',
                provider_message_id VARCHAR(120) NULL,
                recipient_email VARCHAR(190) NOT NULL,
                recipient_user_id INT UNSIGNED NULL,
                subject VARCHAR(255) NOT NULL,
                template_key VARCHAR(80) NULL,
                status ENUM('queued','sent','failed','skipped') DEFAULT 'queued',
                error_message TEXT NULL,
                payload_json JSON NULL,
                sent_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email_logs_recipient (recipient_email),
                INDEX idx_email_logs_user_created (recipient_user_id, created_at),
                INDEX idx_email_logs_status_created (status, created_at),
                INDEX idx_email_logs_provider_message (provider_message_id),
                CONSTRAINT fk_email_logs_user FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS email_logs;');
    }
}
