<?php

class Migration_161_ContactReplies
{
    public function up(\PDO $pdo): void
    {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `contact_replies` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `contact_submission_id` INT UNSIGNED NOT NULL,
                `sender_user_id` INT UNSIGNED NULL,
                `recipient_email` VARCHAR(160) NOT NULL,
                `subject` VARCHAR(220) NOT NULL,
                `body` TEXT NOT NULL,
                `delivery_status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
                `provider_message_id` VARCHAR(120) NULL,
                `error_message` VARCHAR(500) NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_contact_replies_submission` (`contact_submission_id`, `created_at`),
                KEY `idx_contact_replies_sender` (`sender_user_id`, `created_at`),
                KEY `idx_contact_replies_delivery` (`delivery_status`, `created_at`),
                CONSTRAINT `fk_contact_replies_submission`
                    FOREIGN KEY (`contact_submission_id`) REFERENCES `contact_submissions`(`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_contact_replies_sender`
                    FOREIGN KEY (`sender_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS `contact_replies`');
    }
}
