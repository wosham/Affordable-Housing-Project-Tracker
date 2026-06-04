-- ============================================================
-- AHPTC — Trans-Nzoia Affordable Housing Programme Tracker
-- Complete Database Schema — 66 Tables + Migration Tracker
-- Server: MariaDB 10.4+ | Charset: utf8mb4_unicode_ci
-- IMPORT VIA: phpMyAdmin > Import > Select this file > Go
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `trans_nzoia_affordable_housing`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `trans_nzoia_affordable_housing`;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`             VARCHAR(60)  NOT NULL,
    `slug`             VARCHAR(60)  NOT NULL UNIQUE,
    `color`            VARCHAR(20)  NOT NULL DEFAULT '#163300',
    `permissions_json` TEXT         NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `first_name`             VARCHAR(80)  NOT NULL,
    `last_name`              VARCHAR(80)  NOT NULL,
    `email`                  VARCHAR(160) NOT NULL UNIQUE,
    `phone`                  VARCHAR(30)  NULL,
    `password_hash`          VARCHAR(255) NOT NULL,
    `avatar`                 VARCHAR(255) NULL,
    `job_title`              VARCHAR(100) NULL,
    `department`             VARCHAR(100) NULL,
    `role_id`                INT UNSIGNED NOT NULL,
    `status`                 ENUM('active','inactive','suspended') DEFAULT 'active',
    `is_public`              TINYINT(1)   DEFAULT 0,
    `assigned_projects_json` TEXT         NULL,
    `last_login`             DATETIME     NULL,
    `created_at`             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. USER ROLE ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_role_assignments` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `role_id`     INT UNSIGNED NOT NULL,
    `project_id`  INT UNSIGNED NULL,
    `assigned_by` INT UNSIGNED NOT NULL,
    `assigned_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. USER SESSIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `token`         VARCHAR(255) NOT NULL UNIQUE,
    `ip`            VARCHAR(45)  NULL,
    `last_activity` DATETIME     NOT NULL,
    `expires_at`    DATETIME     NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. PASSWORD RESETS
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`            INT UNSIGNED NOT NULL,
    `email`              VARCHAR(190) NULL,
    `token`              VARCHAR(255) NOT NULL UNIQUE,
    `token_hash`         CHAR(64) NULL,
    `expires_at`         DATETIME     NOT NULL,
    `used`               TINYINT(1)   DEFAULT 0,
    `used_at`            DATETIME NULL,
    `created_ip`         VARCHAR(45) NULL,
    `created_user_agent` VARCHAR(255) NULL,
    `created_at`         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_password_resets_hash` (`token_hash`),
    INDEX `idx_password_resets_user_used` (`user_id`, `used`, `expires_at`),
    INDEX `idx_password_resets_email_created` (`email`, `created_at`),
    INDEX `idx_password_resets_ip_created` (`created_ip`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NULL,
    `actor_role`   VARCHAR(80)  NULL,
    `action`       VARCHAR(100) NOT NULL,
    `module`       VARCHAR(80)  NOT NULL,
    `target_id`    INT UNSIGNED DEFAULT 0,
    `details_json` TEXT         NULL,
    `ip`           VARCHAR(45)  NULL,
    `user_agent`   VARCHAR(255) NULL,
    `request_method` VARCHAR(10) NULL,
    `route`        VARCHAR(255) NULL,
    `severity`     ENUM('info','warning','critical') DEFAULT 'info',
    `event_hash`   CHAR(64) NULL,
    `metadata_json` JSON NULL,
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user`    (`user_id`),
    INDEX `idx_module`  (`module`),
    INDEX `idx_created` (`created_at`),
    INDEX `idx_audit_action_created` (`action`, `created_at`),
    INDEX `idx_audit_module_created` (`module`, `created_at`),
    INDEX `idx_audit_severity_created` (`severity`, `created_at`),
    INDEX `idx_audit_ip_created` (`ip`, `created_at`),
    INDEX `idx_audit_target` (`module`, `target_id`),
    INDEX `idx_audit_actor_role` (`actor_role`, `created_at`),
    INDEX `idx_audit_event_hash` (`event_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ANNOUNCEMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id`         INT UNSIGNED NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `body`              TEXT         NOT NULL,
    `type`              VARCHAR(60)  DEFAULT 'info',
    `status`            ENUM('draft','published','archived') DEFAULT 'draft',
    `priority`          ENUM('low','normal','high','urgent') DEFAULT 'normal',
    `target_roles_json` TEXT         NULL,
    `is_pinned`         TINYINT(1)   DEFAULT 0,
    `cta_label`         VARCHAR(120) NULL,
    `cta_url`           VARCHAR(255) NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `published_at`      DATETIME     NULL,
    `updated_at`        DATETIME     NULL,
    `expires_at`        DATETIME     NULL,
    `archived_at`       DATETIME     NULL,
    `metadata_json`     LONGTEXT     NULL,
    INDEX `idx_announcements_status` (`status`),
    INDEX `idx_announcements_type` (`type`),
    INDEX `idx_announcements_priority` (`priority`),
    INDEX `idx_announcements_pinned` (`is_pinned`),
    INDEX `idx_announcements_published_at` (`published_at`),
    INDEX `idx_announcements_expires_at` (`expires_at`),
    INDEX `idx_announcements_author` (`author_id`),
    FOREIGN KEY (`author_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. CONSTITUENCIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `constituencies` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`           VARCHAR(100) NOT NULL,
    `slug`           VARCHAR(100) NOT NULL UNIQUE,
    `mp`             VARCHAR(120) NULL,
    `mp_photo`       VARCHAR(255) NULL,
    `description`    TEXT         NULL,
    `population`     INT UNSIGNED NULL,
    `total_units`    INT UNSIGNED DEFAULT 0,
    `total_projects` INT UNSIGNED DEFAULT 0,
    `avg_completion` TINYINT UNSIGNED DEFAULT 0,
    `status`         ENUM('planning','active','completed') DEFAULT 'planning',
    `hero_image`     VARCHAR(255) NULL,
    `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. WARDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `wards` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `constituency_id`  INT UNSIGNED NOT NULL,
    `name`             VARCHAR(100) NOT NULL,
    `slug`             VARCHAR(100) NOT NULL,
    UNIQUE KEY `uq_wards_constituency_slug` (`constituency_id`, `slug`),
    FOREIGN KEY (`constituency_id`) REFERENCES `constituencies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. PROJECT CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_categories` (
    `id`    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`  VARCHAR(100) NOT NULL,
    `slug`  VARCHAR(100) NOT NULL UNIQUE,
    `icon`  VARCHAR(80)  NULL,
    `color` VARCHAR(20)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. PROJECTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `projects` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id`      INT UNSIGNED NOT NULL,
    `constituency_id`  INT UNSIGNED NOT NULL,
    `ward_id`          INT UNSIGNED NULL,
    `name`             VARCHAR(200) NOT NULL,
    `slug`             VARCHAR(200) NOT NULL UNIQUE,
    `location_label`   VARCHAR(200) NULL,
    `status`           ENUM('planning','active','on_hold','stalled','completed','cancelled') DEFAULT 'planning',
    `pct_complete`     TINYINT UNSIGNED DEFAULT 0,
    `contract_sum`     DECIMAL(15,2) NULL,
    `start_date`       DATE NULL,
    `est_delivery`     DATE NULL,
    `current_milestone` VARCHAR(255) NULL,
    `contractor_name`  VARCHAR(180) NULL,
    `contractor_id`    INT UNSIGNED NULL,
    `consultant_id`    INT UNSIGNED NULL,
    `description`      TEXT NULL,
    `hero_image`       VARCHAR(255) NULL,
    `images_json`      TEXT NULL,
    `funding_source`   VARCHAR(150) NULL,
    `lead_agency`      VARCHAR(150) NULL,
    `site_engineer`    VARCHAR(150) NULL,
    `units`            INT UNSIGNED NULL,
    `is_featured`      TINYINT(1)   DEFAULT 0,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`)     REFERENCES `project_categories`(`id`),
    FOREIGN KEY (`constituency_id`) REFERENCES `constituencies`(`id`),
    INDEX `idx_status`   (`status`),
    INDEX `idx_featured` (`is_featured`),
    INDEX `idx_projects_consultant_status` (`consultant_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. GEO FENCES
-- ============================================================
CREATE TABLE IF NOT EXISTS `geo_fences` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`    INT UNSIGNED NOT NULL,
    `site_name`     VARCHAR(150) NOT NULL,
    `latitude`      DECIMAL(10,8) NOT NULL,
    `longitude`     DECIMAL(11,8) NOT NULL,
    `radius_meters` INT UNSIGNED DEFAULT 200,
    `status`        VARCHAR(40)  DEFAULT 'configured',
    `verified_by`   INT UNSIGNED NULL,
    `verified_at`   DATETIME     NULL,
    `notes`         TEXT         NULL,
    `created_by`    INT UNSIGNED NOT NULL,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. PROJECT ASSIGNMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_assignments` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED NOT NULL,
    `role`        VARCHAR(60)  NOT NULL,
    `assignment_type` VARCHAR(40) NOT NULL DEFAULT 'site',
    `scope`       VARCHAR(80)  NOT NULL DEFAULT 'general',
    `status`      VARCHAR(30)  NOT NULL DEFAULT 'active',
    `start_date`  DATE NULL,
    `end_date`    DATE NULL,
    `is_primary`  TINYINT(1) NOT NULL DEFAULT 0,
    `notes`       TEXT NULL,
    `revoked_by`  INT UNSIGNED NULL,
    `revoked_at`  DATETIME NULL,
    `updated_by`  INT UNSIGNED NULL,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `assigned_by` INT UNSIGNED NOT NULL,
    `assigned_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    UNIQUE KEY `uq_project_user` (`project_id`, `user_id`),
    INDEX `idx_project_assignments_user_project` (`user_id`, `project_id`),
    INDEX `idx_project_assignments_user_project_status` (`user_id`, `project_id`, `status`),
    INDEX `idx_project_assignments_project_role_status` (`project_id`, `role`, `status`),
    INDEX `idx_project_assignments_user_status` (`user_id`, `status`),
    INDEX `idx_assignments_project_user_status` (`project_id`, `user_id`, `status`),
    INDEX `idx_project_assignments_status_dates` (`status`, `start_date`, `end_date`),
    INDEX `idx_project_assignments_primary` (`project_id`, `role`, `is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13B. MANAGER PROJECT MONITORING NOTES
-- ============================================================
CREATE TABLE IF NOT EXISTS `manager_project_notes` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT UNSIGNED NOT NULL,
    `manager_id` INT UNSIGNED NOT NULL,
    `note_type` VARCHAR(40) NOT NULL DEFAULT 'monitoring',
    `title`      VARCHAR(180) NOT NULL,
    `body`       TEXT NULL,
    `severity`   VARCHAR(30) NOT NULL DEFAULT 'normal',
    `status`     VARCHAR(30) NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`manager_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_manager_project_notes_project_status` (`project_id`, `status`),
    INDEX `idx_manager_project_notes_manager_created` (`manager_id`, `created_at`),
    INDEX `idx_manager_project_notes_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. MILESTONES
-- ============================================================
CREATE TABLE IF NOT EXISTS `milestones` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `label`            VARCHAR(200) NOT NULL,
    `description`      TEXT NULL,
    `target_date`      DATE NULL,
    `actual_date`      DATE NULL,
    `status`           ENUM('pending','current','done') DEFAULT 'pending',
    `progress_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `priority`         VARCHAR(20) NOT NULL DEFAULT 'normal',
    `sequence`         SMALLINT UNSIGNED DEFAULT 0,
    `updated_by`       INT UNSIGNED NULL,
    `completed_by`     INT UNSIGNED NULL,
    `notes`            TEXT NULL,
    `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`completed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_project_seq` (`project_id`, `sequence`),
    INDEX `idx_milestones_project_status` (`project_id`, `status`),
    INDEX `idx_milestones_target_date` (`target_date`),
    INDEX `idx_milestones_priority` (`priority`),
    INDEX `idx_milestones_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `milestone_updates` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `milestone_id`         INT UNSIGNED NOT NULL,
    `project_id`           INT UNSIGNED NOT NULL,
    `user_id`              INT UNSIGNED NULL,
    `old_status`           VARCHAR(30) NULL,
    `new_status`           VARCHAR(30) NULL,
    `old_target_date`      DATE NULL,
    `new_target_date`      DATE NULL,
    `old_progress_percent` TINYINT UNSIGNED NULL,
    `new_progress_percent` TINYINT UNSIGNED NULL,
    `note`                 TEXT NULL,
    `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`milestone_id`) REFERENCES `milestones`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_milestone_updates_milestone` (`milestone_id`, `created_at`),
    INDEX `idx_milestone_updates_project` (`project_id`, `created_at`),
    INDEX `idx_milestone_updates_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. SUBCONTRACTORS
-- ============================================================
CREATE TABLE IF NOT EXISTS `subcontractors` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`     INT UNSIGNED NOT NULL,
    `company`        VARCHAR(200) NOT NULL,
    `scope_of_work`  TEXT NULL,
    `contract_value` DECIMAL(15,2) NULL,
    `status`         VARCHAR(40)  DEFAULT 'active',
    `contact_person` VARCHAR(150) NULL,
    `phone`          VARCHAR(60) NULL,
    `email`          VARCHAR(180) NULL,
    `compliance_status` ENUM('pending','compliant','issue','expired') DEFAULT 'pending',
    `risk_status`    ENUM('normal','watch','high','critical') DEFAULT 'normal',
    `performance_note` TEXT NULL,
    `updated_by`     INT UNSIGNED NULL,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subcontractors_project_status` (`project_id`, `status`),
    INDEX `idx_subcontractors_compliance` (`compliance_status`, `risk_status`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 16. DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `documents` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`      INT UNSIGNED NOT NULL,
    `uploaded_by`     INT UNSIGNED NOT NULL,
    `category`        ENUM('contract','drawing','spec','report','correspondence','shop-drawing','quality-test','other') DEFAULT 'other',
    `filename`        VARCHAR(255) NOT NULL,
    `original_name`   VARCHAR(255) NOT NULL,
    `size`            INT UNSIGNED NOT NULL,
    `version`         VARCHAR(20)  DEFAULT '1.0',
    `description`     TEXT NULL,
    `is_confidential` TINYINT(1)   DEFAULT 0,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`),
    INDEX `idx_documents_project_created` (`project_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 17. BOQ ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `boq_items` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`    INT UNSIGNED NOT NULL,
    `section`       VARCHAR(150) NOT NULL,
    `item_no`       VARCHAR(20)  NOT NULL,
    `description`   TEXT         NOT NULL,
    `unit`          VARCHAR(30)  NOT NULL,
    `quantity`      DECIMAL(12,3) DEFAULT 0,
    `rate`          DECIMAL(12,2) DEFAULT 0,
    `amount`        DECIMAL(15,2) DEFAULT 0,
    `certified_qty` DECIMAL(12,3) DEFAULT 0,
    `paid_qty`      DECIMAL(12,3) DEFAULT 0,
    `status`        VARCHAR(40)  DEFAULT 'active',
    `review_status` VARCHAR(30)  DEFAULT 'pending',
    `risk_status`   VARCHAR(30)  DEFAULT 'normal',
    `manager_note`  TEXT NULL,
    `last_reviewed_by` INT UNSIGNED NULL,
    `last_reviewed_at` DATETIME NULL,
    `certified_updated_by` INT UNSIGNED NULL,
    `paid_updated_by` INT UNSIGNED NULL,
    `updated_by`    INT UNSIGNED NULL,
    `last_certified_at` DATETIME NULL,
    `last_paid_at`  DATETIME NULL,
    `notes`         TEXT NULL,
    `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_boq_project` (`project_id`),
    INDEX `idx_boq_project_section` (`project_id`, `section`),
    INDEX `idx_boq_project_status` (`project_id`, `status`),
    INDEX `idx_boq_project_review` (`project_id`, `review_status`),
    INDEX `idx_boq_project_risk` (`project_id`, `risk_status`),
    INDEX `idx_boq_project_item_no` (`project_id`, `item_no`),
    INDEX `idx_boq_reviewed_by` (`last_reviewed_by`),
    INDEX `idx_boq_item_no` (`item_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `boq_review_updates` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `boq_item_id`       INT UNSIGNED NOT NULL,
    `project_id`        INT UNSIGNED NOT NULL,
    `user_id`           INT UNSIGNED NULL,
    `old_certified_qty` DECIMAL(12,3) NULL,
    `new_certified_qty` DECIMAL(12,3) NULL,
    `old_paid_qty`      DECIMAL(12,3) NULL,
    `new_paid_qty`      DECIMAL(12,3) NULL,
    `old_review_status` VARCHAR(30) NULL,
    `new_review_status` VARCHAR(30) NULL,
    `old_risk_status`   VARCHAR(30) NULL,
    `new_risk_status`   VARCHAR(30) NULL,
    `note`              TEXT NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`boq_item_id`) REFERENCES `boq_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_boq_review_updates_item` (`boq_item_id`, `created_at`),
    INDEX `idx_boq_review_updates_project` (`project_id`, `created_at`),
    INDEX `idx_boq_review_updates_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 18. PROGRAMME TASKS
-- ============================================================
CREATE TABLE IF NOT EXISTS `programme_tasks` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`          INT UNSIGNED NOT NULL,
    `task_name`           VARCHAR(200) NOT NULL,
    `start_date`          DATE NULL,
    `end_date`            DATE NULL,
    `planned_start`       DATE NULL,
    `planned_end`         DATE NULL,
    `pct_complete`        TINYINT UNSIGNED DEFAULT 0,
    `depends_on_task_id`  INT UNSIGNED NULL,
    `assigned_to`         INT UNSIGNED NULL,
    `status`              VARCHAR(40)  DEFAULT 'pending',
    `sort_order`          INT UNSIGNED DEFAULT 0,
    `critical_path`       TINYINT(1) DEFAULT 0,
    `baseline_start`      DATE NULL,
    `baseline_end`        DATE NULL,
    `notes`               TEXT NULL,
    `updated_by`          INT UNSIGNED NULL,
    `created_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_programme_project` (`project_id`),
    INDEX `idx_programme_project_status` (`project_id`, `status`),
    INDEX `idx_programme_project_dates` (`project_id`, `planned_start`, `planned_end`),
    INDEX `idx_programme_assigned_to` (`assigned_to`),
    INDEX `idx_programme_dependency` (`depends_on_task_id`),
    INDEX `idx_programme_project_status_end` (`project_id`, `status`, `end_date`),
    INDEX `idx_programme_project_critical_end` (`project_id`, `critical_path`, `planned_end`),
    INDEX `idx_programme_project_assignee_status` (`project_id`, `assigned_to`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. EQUIPMENT REGISTER
-- ============================================================
CREATE TABLE IF NOT EXISTS `equipment_register` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`     INT UNSIGNED NOT NULL,
    `equipment_type` VARCHAR(120) NOT NULL,
    `registration`   VARCHAR(60)  NULL,
    `owner`          VARCHAR(150) NULL,
    `date_on_site`   DATE NULL,
    `date_off_site`  DATE NULL,
    `condition`      VARCHAR(60)  DEFAULT 'good',
    `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 20. MATERIAL DELIVERIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `material_deliveries` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `material`         VARCHAR(150) NOT NULL,
    `supplier`         VARCHAR(150) NULL,
    `delivery_date`    DATE         NOT NULL,
    `quantity`         DECIMAL(12,3) NOT NULL,
    `unit`             VARCHAR(30)  NOT NULL,
    `delivery_note_no` VARCHAR(60)  NULL,
    `received_by`      INT UNSIGNED NOT NULL,
    `condition`        VARCHAR(60)  DEFAULT 'good',
    `approved`         TINYINT(1)   DEFAULT 0,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`received_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 21. MATERIAL APPROVALS
-- ============================================================
CREATE TABLE IF NOT EXISTS `material_approvals` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`     INT UNSIGNED NOT NULL,
    `material`       VARCHAR(150) NOT NULL,
    `specification`  TEXT NULL,
    `submitted_by`   INT UNSIGNED NOT NULL,
    `submitted_date` DATE         NOT NULL,
    `approved_by`    INT UNSIGNED NULL,
    `approved_date`  DATE         NULL,
    `status`         ENUM('pending','approved','rejected') DEFAULT 'pending',
    `notes`          TEXT NULL,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`),
    INDEX `idx_material_approvals_project_status_date` (`project_id`, `status`, `submitted_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 22. LABOUR REGISTER
-- ============================================================
CREATE TABLE IF NOT EXISTS `labour_register` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `diary_date`       DATE         NOT NULL,
    `skilled_count`    SMALLINT UNSIGNED DEFAULT 0,
    `unskilled_count`  SMALLINT UNSIGNED DEFAULT 0,
    `supervisor_count` SMALLINT UNSIGNED DEFAULT 0,
    `total`            SMALLINT UNSIGNED DEFAULT 0,
    `recorded_by`      INT UNSIGNED NOT NULL,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`),
    UNIQUE KEY `uq_project_date` (`project_id`, `diary_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 23. SITE DIARIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `site_diaries` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`    INT UNSIGNED NOT NULL,
    `diary_date`    DATE         NOT NULL,
    `work_done`     TEXT NULL,
    `issues_raised` TEXT NULL,
    `next_day_plan` TEXT NULL,
    `recorded_by`   INT UNSIGNED NOT NULL,
    `approved_by`   INT UNSIGNED NULL,
    `approved_at`   DATETIME     NULL,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`),
    UNIQUE KEY `uq_project_date` (`project_id`, `diary_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 24. WEATHER LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `weather_logs` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`          INT UNSIGNED NOT NULL,
    `log_date`            DATE         NOT NULL,
    `morning_condition`   VARCHAR(60)  NULL,
    `afternoon_condition` VARCHAR(60)  NULL,
    `rainfall_mm`         DECIMAL(5,1) DEFAULT 0,
    `working_hours`       DECIMAL(4,1) DEFAULT 8,
    `remarks`             TEXT NULL,
    `recorded_by`         INT UNSIGNED NOT NULL,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 25. SITE MEETING MINUTES
-- ============================================================
CREATE TABLE IF NOT EXISTS `site_meeting_minutes` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `meeting_date`     DATE         NOT NULL,
    `venue`            VARCHAR(200) NULL,
    `attendees_json`   TEXT NULL,
    `agenda`           TEXT NULL,
    `minutes_text`     LONGTEXT NULL,
    `action_items_json` TEXT NULL,
    `document_path`    VARCHAR(255) NULL,
    `recorded_by`      INT UNSIGNED NOT NULL,
    `status`           ENUM('draft','recorded','reviewed','closed') DEFAULT 'recorded',
    `action_status`    ENUM('none','open','in-progress','completed','overdue') DEFAULT 'none',
    `updated_by`       INT UNSIGNED NULL,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `reviewed_at`      DATETIME NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_smm_project_date` (`project_id`, `meeting_date`),
    INDEX `idx_smm_status` (`status`, `meeting_date`),
    INDEX `idx_smm_action_status` (`action_status`, `meeting_date`),
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 26. COMMUNITY LIAISON
-- ============================================================
CREATE TABLE IF NOT EXISTS `community_liaison` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`      INT UNSIGNED NOT NULL,
    `log_date`        DATE         NOT NULL,
    `engagement_type` VARCHAR(100) NULL,
    `community_rep`   VARCHAR(150) NULL,
    `issues_raised`   TEXT NULL,
    `resolution`      TEXT NULL,
    `follow_up_date`  DATE NULL,
    `recorded_by`     INT UNSIGNED NOT NULL,
    `status`          ENUM('open','follow-up','resolved','closed') DEFAULT 'open',
    `updated_by`      INT UNSIGNED NULL,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `closed_at`       DATETIME NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_cl_project_date` (`project_id`, `log_date`),
    INDEX `idx_cl_status_followup` (`status`, `follow_up_date`),
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 27. H&S INCIDENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `hs_incidents` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `incident_date`    DATE         NOT NULL,
    `incident_type`    ENUM('near-miss','first-aid','medical','fatality') NOT NULL,
    `description`      TEXT         NOT NULL,
    `persons_involved` TEXT NULL,
    `cause`            TEXT NULL,
    `corrective_action` TEXT NULL,
    `reported_by`      INT UNSIGNED NOT NULL,
    `severity`         ENUM('low','medium','high','critical') DEFAULT 'medium',
    `status`           ENUM('open','investigating','action-pending','resolved','closed') DEFAULT 'open',
    `follow_up_date`   DATE NULL,
    `closed_at`        DATETIME NULL,
    `updated_by`       INT UNSIGNED NULL,
    `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `attachment_path`  VARCHAR(255) NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_hs_project_date` (`project_id`, `incident_date`),
    INDEX `idx_hs_status_severity` (`status`, `severity`),
    INDEX `idx_hs_followup` (`follow_up_date`, `status`),
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 28. ENVIRONMENTAL LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `environmental_logs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `log_date`         DATE         NOT NULL,
    `observation_type` VARCHAR(100) NULL,
    `description`      TEXT         NOT NULL,
    `action_taken`     TEXT NULL,
    `recorded_by`      INT UNSIGNED NOT NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 29. QUALITY TESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `quality_tests` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `test_type`        VARCHAR(100) NOT NULL,
    `test_date`        DATE         NOT NULL,
    `location_on_site` VARCHAR(200) NULL,
    `result`           VARCHAR(150) NULL,
    `pass_fail`        ENUM('pass','fail','pending') DEFAULT 'pending',
    `lab_ref`          VARCHAR(80)  NULL,
    `tested_by`        INT UNSIGNED NOT NULL,
    `document_path`    VARCHAR(255) NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tested_by`)  REFERENCES `users`(`id`),
    INDEX `idx_quality_tests_project_result_date` (`project_id`, `pass_fail`, `test_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 30. INSPECTION TEST PLANS
-- ============================================================
CREATE TABLE IF NOT EXISTS `inspection_test_plans` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `activity`         VARCHAR(200) NOT NULL,
    `hold_point`       VARCHAR(100) NULL,
    `inspection_date`  DATE NULL,
    `inspected_by`     INT UNSIGNED NULL,
    `outcome`          VARCHAR(100) NULL,
    `witness_required` TINYINT(1)   DEFAULT 0,
    `document_path`    VARCHAR(255) NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`inspected_by`) REFERENCES `users`(`id`),
    INDEX `idx_itp_project_date` (`project_id`, `inspection_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 31. NON CONFORMANCE REPORTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `non_conformance_reports` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`        INT UNSIGNED NOT NULL,
    `raised_by`         INT UNSIGNED NOT NULL,
    `raised_date`       DATE         NOT NULL,
    `description`       TEXT         NOT NULL,
    `severity`          ENUM('minor','major','critical') DEFAULT 'minor',
    `root_cause`        TEXT NULL,
    `corrective_action` TEXT NULL,
    `closed_by`         INT UNSIGNED NULL,
    `closed_date`       DATE NULL,
    `status`            ENUM('open','in-progress','closed') DEFAULT 'open',
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`raised_by`)  REFERENCES `users`(`id`),
    FOREIGN KEY (`closed_by`)  REFERENCES `users`(`id`),
    INDEX `idx_ncr_project_status_date` (`project_id`, `status`, `raised_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 32. SHOP DRAWINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `shop_drawings` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`     INT UNSIGNED NOT NULL,
    `drawing_no`     VARCHAR(60)  NOT NULL,
    `title`          VARCHAR(200) NOT NULL,
    `submitted_by`   INT UNSIGNED NOT NULL,
    `submitted_date` DATE         NOT NULL,
    `revision`       VARCHAR(10)  DEFAULT 'A',
    `status`         ENUM('under-review','approved','rejected','resubmit') DEFAULT 'under-review',
    `reviewed_by`    INT UNSIGNED NULL,
    `review_date`    DATE NULL,
    `document_path`  VARCHAR(255) NULL,
    `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`reviewed_by`)  REFERENCES `users`(`id`),
    INDEX `idx_shop_drawings_project_status_date` (`project_id`, `status`, `submitted_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 33. DEFECTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `defects` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED NOT NULL,
    `raised_by`   INT UNSIGNED NOT NULL,
    `raised_date` DATE         NOT NULL,
    `location`    VARCHAR(200) NULL,
    `description` TEXT         NOT NULL,
    `severity`    ENUM('minor','major','critical') DEFAULT 'minor',
    `photo_path`  VARCHAR(255) NULL,
    `assigned_to` INT UNSIGNED NULL,
    `due_date`    DATE NULL,
    `closed_date` DATE NULL,
    `status`      ENUM('open','in-progress','resolved','closed') DEFAULT 'open',
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)  REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`raised_by`)   REFERENCES `users`(`id`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`),
    INDEX `idx_defects_project_status_date` (`project_id`, `status`, `raised_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 34. IPCs (Interim Payment Certificates)
-- ============================================================
CREATE TABLE IF NOT EXISTS `ipcs` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`       INT UNSIGNED NOT NULL,
    `contractor_id`    INT UNSIGNED NOT NULL,
    `ipc_number`       SMALLINT UNSIGNED NOT NULL,
    `period_from`      DATE         NOT NULL,
    `period_to`        DATE         NOT NULL,
    `gross_amount`     DECIMAL(15,2) DEFAULT 0,
    `retention_amount` DECIMAL(15,2) DEFAULT 0,
    `net_amount`       DECIMAL(15,2) DEFAULT 0,
    `status`           ENUM('draft','submitted','clerk-endorsed','certified','endorsed','approved','rejected','paid') DEFAULT 'draft',
    `submitted_at`     DATETIME NULL,
    `certified_at`     DATETIME NULL,
    `certified_by`      INT UNSIGNED NULL,
    `certification_comment` TEXT NULL,
    `approved_at`      DATETIME NULL,
    `approved_by`      INT UNSIGNED NULL,
    `rejected_by`      INT UNSIGNED NULL,
    `rejected_at`      DATETIME NULL,
    `rejection_reason` TEXT NULL,
    `paid_at`          DATETIME NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)    REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contractor_id`) REFERENCES `users`(`id`),
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`rejected_by`) REFERENCES `users`(`id`),
    UNIQUE KEY `uq_project_ipc_no` (`project_id`, `ipc_number`),
    INDEX `idx_ipcs_status` (`status`),
    INDEX `idx_ipcs_project_status` (`project_id`, `status`),
    INDEX `idx_ipcs_project_status_submitted` (`project_id`, `status`, `submitted_at`),
    INDEX `idx_ipcs_project_status_certified` (`project_id`, `status`, `certified_at`),
    INDEX `idx_ipcs_contractor_status` (`contractor_id`, `status`),
    INDEX `idx_ipcs_submitted_at` (`submitted_at`),
    INDEX `idx_ipcs_approved_at` (`approved_at`),
    INDEX `idx_ipcs_paid_at` (`paid_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 35. IPC LINES
-- ============================================================
CREATE TABLE IF NOT EXISTS `ipc_lines` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ipc_id`          INT UNSIGNED NOT NULL,
    `boq_item_id`     INT UNSIGNED NULL,
    `description`     TEXT         NOT NULL,
    `qty_this_period` DECIMAL(12,3) DEFAULT 0,
    `cumulative_qty`  DECIMAL(12,3) DEFAULT 0,
    `rate`            DECIMAL(12,2) DEFAULT 0,
    `amount`          DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (`ipc_id`)      REFERENCES `ipcs`(`id`)      ON DELETE CASCADE,
    FOREIGN KEY (`boq_item_id`) REFERENCES `boq_items`(`id`) ON DELETE SET NULL,
    INDEX `idx_ipc_lines_ipc` (`ipc_id`),
    INDEX `idx_ipc_lines_ipc_boq` (`ipc_id`, `boq_item_id`),
    INDEX `idx_ipc_lines_boq` (`boq_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 36. IPC APPROVALS
-- ============================================================
CREATE TABLE IF NOT EXISTS `ipc_approvals` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ipc_id`      INT UNSIGNED NOT NULL,
    `step`        TINYINT UNSIGNED NOT NULL COMMENT '1=Clerk, 2=Consultant, 3=Manager, 4=Director',
    `action_by`   INT UNSIGNED NOT NULL,
    `action`      ENUM('endorsed','certified','approved','rejected') NOT NULL,
    `comments`    TEXT NULL,
    `actioned_at` DATETIME     NOT NULL,
    FOREIGN KEY (`ipc_id`)    REFERENCES `ipcs`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`action_by`) REFERENCES `users`(`id`),
    INDEX `idx_ipc_approvals_ipc_step` (`ipc_id`, `step`),
    INDEX `idx_ipc_approvals_ipc_step_actioned` (`ipc_id`, `step`, `actioned_at`),
    INDEX `idx_ipc_approvals_action` (`action`),
    INDEX `idx_ipc_approvals_actioned_at` (`actioned_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 37. VARIATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `variations` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`          INT UNSIGNED NOT NULL,
    `submitted_by`        INT UNSIGNED NOT NULL,
    `vo_number`           SMALLINT UNSIGNED NOT NULL,
    `description`         TEXT         NOT NULL,
    `reason`              TEXT NULL,
    `amount`              DECIMAL(15,2) DEFAULT 0,
    `impact_on_time_days` SMALLINT     DEFAULT 0,
    `status`              ENUM('pending','approved','rejected') DEFAULT 'pending',
    `approved_by`         INT UNSIGNED NULL,
    `approved_at`         DATETIME NULL,
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`),
    INDEX `idx_variations_project_status_created` (`project_id`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 38. EOT REQUESTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `eot_requests` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`          INT UNSIGNED NOT NULL,
    `submitted_by`        INT UNSIGNED NOT NULL,
    `eot_number`          SMALLINT UNSIGNED NOT NULL,
    `days_requested`      SMALLINT UNSIGNED NOT NULL,
    `reason`              TEXT         NOT NULL,
    `supporting_evidence` VARCHAR(255) NULL,
    `status`              ENUM('pending','granted','partially-granted','rejected') DEFAULT 'pending',
    `granted_days`        SMALLINT UNSIGNED NULL,
    `approved_by`         INT UNSIGNED NULL,
    `approved_at`         DATETIME NULL,
    `manager_recommendation` ENUM('pending','approve','partial','reject','clarification') DEFAULT 'pending',
    `manager_recommended_days` SMALLINT UNSIGNED NULL,
    `manager_review_note` TEXT NULL,
    `manager_reviewed_by` INT UNSIGNED NULL,
    `manager_reviewed_at` DATETIME NULL,
    `delay_category`      VARCHAR(80) NULL,
    `impact_summary`      TEXT NULL,
    `updated_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_eot_project_status` (`project_id`, `status`),
    INDEX `idx_eot_project_status_created` (`project_id`, `status`, `created_at`),
    INDEX `idx_eot_manager_review` (`manager_recommendation`, `manager_reviewed_at`),
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`),
    FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 39. LIQUIDATED DAMAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `liquidated_damages` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`        INT UNSIGNED NOT NULL,
    `rate_per_day`      DECIMAL(12,2) NOT NULL,
    `days_overdue`      SMALLINT UNSIGNED DEFAULT 0,
    `total_ld`          DECIMAL(15,2) DEFAULT 0,
    `applied_to_ipc_id` INT UNSIGNED NULL,
    `notes`             TEXT NULL,
    `status`            ENUM('draft','pending','applied','suspended','waived') DEFAULT 'draft',
    `calculated_by`     INT UNSIGNED NULL,
    `updated_by`        INT UNSIGNED NULL,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `applied_at`        DATETIME NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ld_project_status` (`project_id`, `status`),
    INDEX `idx_ld_ipc` (`applied_to_ipc_id`),
    FOREIGN KEY (`project_id`)        REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`applied_to_ipc_id`) REFERENCES `ipcs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 40. PAYMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ipc_id`       INT UNSIGNED NOT NULL,
    `project_id`   INT UNSIGNED NOT NULL,
    `amount`       DECIMAL(15,2) NOT NULL,
    `payment_date` DATE         NOT NULL,
    `reference_no` VARCHAR(100) NULL,
    `bank`         VARCHAR(150) NULL,
    `processed_by` INT UNSIGNED NOT NULL,
    `receipt_path` VARCHAR(255) NULL,
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ipc_id`)       REFERENCES `ipcs`(`id`)     ON DELETE CASCADE,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 41. RETENTION
-- ============================================================
CREATE TABLE IF NOT EXISTS `retention` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`      INT UNSIGNED NOT NULL,
    `total_held`      DECIMAL(15,2) DEFAULT 0,
    `released_amount` DECIMAL(15,2) DEFAULT 0,
    `release_date`    DATE NULL,
    `release_reason`  TEXT NULL,
    `processed_by`    INT UNSIGNED NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 42. RFIs (Requests For Information)
-- ============================================================
CREATE TABLE IF NOT EXISTS `rfis` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`    INT UNSIGNED NOT NULL,
    `raised_by`     INT UNSIGNED NOT NULL,
    `rfi_number`    SMALLINT UNSIGNED NOT NULL,
    `subject`       VARCHAR(200) NOT NULL,
    `description`   TEXT         NOT NULL,
    `urgency`       ENUM('low','normal','urgent') DEFAULT 'normal',
    `responded_by`  INT UNSIGNED NULL,
    `response`      TEXT NULL,
    `raised_date`   DATE         NOT NULL,
    `response_date` DATE NULL,
    `status`        ENUM('open','answered','closed') DEFAULT 'open',
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)   REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`raised_by`)    REFERENCES `users`(`id`),
    FOREIGN KEY (`responded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 43. ATTENDANCE GATEWAYS
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_gateways` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED NOT NULL,
    `date`        DATE         NOT NULL,
    `opened_by`   INT UNSIGNED NOT NULL,
    `opened_at`   DATETIME     NOT NULL,
    `closes_at`   DATETIME     NOT NULL,
    `is_open`     TINYINT(1)   DEFAULT 1,
    `notes`       TEXT NULL,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`opened_by`)  REFERENCES `users`(`id`),
    UNIQUE KEY `uq_project_date` (`project_id`, `date`),
    INDEX `idx_gateways_project_date_open` (`project_id`, `date`, `is_open`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 44. ATTENDANCE RECORDS
-- ============================================================
CREATE TABLE IF NOT EXISTS `attendance_records` (
    `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`              INT UNSIGNED NOT NULL,
    `project_id`           INT UNSIGNED NOT NULL,
    `gateway_id`           INT UNSIGNED NOT NULL,
    `date`                 DATE         NOT NULL,
    `signin_time`          TIME NULL,
    `latitude`             DECIMAL(10,8) NULL,
    `longitude`            DECIMAL(11,8) NULL,
    `distance_from_site_m` DECIMAL(8,1) NULL,
    `status`               ENUM('present','absent','geo-fail','outside-window','late') DEFAULT 'absent',
    `created_at`           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)               ON DELETE CASCADE,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`)            ON DELETE CASCADE,
    FOREIGN KEY (`gateway_id`) REFERENCES `attendance_gateways`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_date` (`user_id`, `date`),
    INDEX `idx_attendance_project_date_status` (`project_id`, `date`, `status`),
    INDEX `idx_attendance_date_user` (`date`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 45. MESSAGE THREADS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_threads` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject`         VARCHAR(255) NOT NULL,
    `type`            ENUM('direct','group','project-channel') DEFAULT 'direct',
    `status`          ENUM('open','archived') DEFAULT 'open',
    `priority`        ENUM('normal','urgent') DEFAULT 'normal',
    `project_id`      INT UNSIGNED NULL,
    `created_by`      INT UNSIGNED NOT NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `last_message_id` INT UNSIGNED NULL,
    `last_message_at` DATETIME NULL,
    `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_threads_last_message` (`last_message_at`),
    INDEX `idx_threads_project` (`project_id`),
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 46. MESSAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `messages` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `thread_id`  INT UNSIGNED NOT NULL,
    `sender_id`  INT UNSIGNED NOT NULL,
    `parent_id`  INT UNSIGNED NULL,
    `body`       TEXT         NOT NULL,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `edited_at`  DATETIME NULL,
    `is_deleted` TINYINT(1)   DEFAULT 0,
    `deleted_by` INT UNSIGNED NULL,
    `deleted_at` DATETIME NULL,
    `metadata_json` JSON NULL,
    INDEX `idx_messages_thread_created` (`thread_id`, `created_at`),
    INDEX `idx_messages_sender` (`sender_id`),
    FOREIGN KEY (`thread_id`) REFERENCES `message_threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 47. MESSAGE PARTICIPANTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_participants` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `thread_id`    INT UNSIGNED NOT NULL,
    `user_id`      INT UNSIGNED NOT NULL,
    `role_at_join` VARCHAR(50) NULL,
    `joined_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `last_read_at` DATETIME NULL,
    `is_admin`     TINYINT(1)   DEFAULT 0,
    `is_muted`     TINYINT(1)   DEFAULT 0,
    `is_archived`  TINYINT(1)   DEFAULT 0,
    `archived_at`  DATETIME NULL,
    INDEX `idx_participants_user` (`user_id`, `is_archived`),
    INDEX `idx_participants_thread` (`thread_id`),
    FOREIGN KEY (`thread_id`) REFERENCES `message_threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)           ON DELETE CASCADE,
    UNIQUE KEY `uq_thread_user` (`thread_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 48. MESSAGE READS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_reads` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id` INT UNSIGNED NOT NULL,
    `user_id`    INT UNSIGNED NOT NULL,
    `read_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_reads_user` (`user_id`),
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    UNIQUE KEY `uq_msg_user` (`message_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 49. MESSAGE ATTACHMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_attachments` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id`    INT UNSIGNED NULL,
    `uploaded_by`   INT UNSIGNED NULL,
    `filename`      VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `size`          INT UNSIGNED NOT NULL,
    `type`          VARCHAR(100) NOT NULL,
    `mime_type`     VARCHAR(120) NULL,
    `path`          VARCHAR(255) NOT NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `download_count` INT UNSIGNED DEFAULT 0,
    `checksum`      VARCHAR(128) NULL,
    `upload_token`  VARCHAR(64) NULL,
    INDEX `idx_attachments_message` (`message_id`),
    INDEX `idx_attachments_token` (`upload_token`),
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 50. PROJECT CHANNELS
-- ============================================================
CREATE TABLE IF NOT EXISTS `project_channels` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED NOT NULL,
    `name`        VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `created_by`  INT UNSIGNED NOT NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 51. MEDIA LIBRARY
-- ============================================================
CREATE TABLE IF NOT EXISTS `media_library` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename`      VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `title`         VARCHAR(255) NULL,
    `path`          VARCHAR(255) NOT NULL,
    `url`           VARCHAR(500) NOT NULL,
    `type`          VARCHAR(100) NOT NULL,
    `size`          INT UNSIGNED NOT NULL,
    `width`         INT UNSIGNED NULL,
    `height`        INT UNSIGNED NULL,
    `extension`     VARCHAR(20)  NULL,
    `alt_text`      VARCHAR(255) NULL,
    `caption`       VARCHAR(500) NULL,
    `uploaded_by`   INT UNSIGNED NOT NULL,
    `folder`        VARCHAR(100) DEFAULT 'general',
    `source`        VARCHAR(60)  DEFAULT 'upload',
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 52. CMS PAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_pages` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug`            VARCHAR(100) NOT NULL UNIQUE,
    `status`          ENUM('published','draft','maintenance','hidden') DEFAULT 'published',
    `template`        VARCHAR(60)  NULL,
    `route_path`      VARCHAR(200) NULL,
    `seo_title`       VARCHAR(255) NULL,
    `seo_description` TEXT NULL,
    `seo_keywords`    TEXT NULL,
    `og_image_id`     INT UNSIGNED NULL,
    `canonical_url`   VARCHAR(500) NULL,
    `hero_image`      VARCHAR(255) NULL,
    `updated_by`      INT UNSIGNED NULL,
    `updated_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 53. CMS SECTIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_sections` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id`      INT UNSIGNED NOT NULL,
    `section_key`  VARCHAR(80)  NOT NULL,
    `label`        VARCHAR(150) NOT NULL,
    `section_type` VARCHAR(40)  NOT NULL DEFAULT 'rich_text',
    `sort_order`   SMALLINT UNSIGNED DEFAULT 0,
    `is_visible`   TINYINT(1)   DEFAULT 1,
    `editor_mode`  VARCHAR(40)  NOT NULL DEFAULT 'structured',
    `is_locked`    TINYINT(1)   DEFAULT 0,
    `content_json` LONGTEXT NULL,
    `updated_by`   INT UNSIGNED NULL,
    FOREIGN KEY (`page_id`) REFERENCES `cms_pages`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_page_section` (`page_id`, `section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 54. CMS SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_settings` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(100) NOT NULL UNIQUE,
    `value`      LONGTEXT NULL,
    `type`       ENUM('text','number','boolean','json','image','color') DEFAULT 'text',
    `label`      VARCHAR(150) NOT NULL,
    `group`      VARCHAR(80)  NOT NULL DEFAULT 'global',
    `updated_by` INT UNSIGNED NULL,
    `updated_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 55. NEWS CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `news_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(100) NOT NULL,
    `slug`        VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT NULL,
    `color`       VARCHAR(20)  DEFAULT '#163300'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 56. NEWS TAGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `news_tags` (
    `id`   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(80) NOT NULL,
    `slug` VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 57. NEWS ARTICLES
-- ============================================================
CREATE TABLE IF NOT EXISTS `news_articles` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_format`       VARCHAR(40) NOT NULL DEFAULT 'article',
    `category_id`       INT UNSIGNED NULL,
    `author_id`         INT UNSIGNED NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `slug`              VARCHAR(255) NOT NULL UNIQUE,
    `excerpt`           TEXT NULL,
    `read_time`         VARCHAR(50) NULL,
    `body`              LONGTEXT     NOT NULL,
    `featured_image_id` INT UNSIGNED NULL,
    `image_caption`     VARCHAR(500) NULL,
    `inline_image_id`   INT UNSIGNED NULL,
    `attachment_id`     INT UNSIGNED NULL,
    `external_url`      VARCHAR(500) NULL,
    `status`            ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
    `is_featured`       TINYINT(1) DEFAULT 0,
    `is_visible`        TINYINT(1) NOT NULL DEFAULT 1,
    `published_at`      DATETIME NULL,
    `scheduled_for`     DATETIME NULL,
    `views`             INT UNSIGNED DEFAULT 0,
    `seo_title`         VARCHAR(255) NULL,
    `seo_description`   TEXT NULL,
    `og_image_id`       INT UNSIGNED NULL,
    `metadata_json`     LONGTEXT NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`        DATETIME NULL,
    FOREIGN KEY (`category_id`) REFERENCES `news_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`author_id`)   REFERENCES `users`(`id`),
    INDEX `idx_status`    (`status`),
    INDEX `idx_published` (`published_at`),
    INDEX `idx_news_format` (`post_format`),
    INDEX `idx_news_visibility` (`is_visible`),
    INDEX `idx_news_deleted` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 58. NEWS ARTICLE TAGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `news_article_tags` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT UNSIGNED NOT NULL,
    `tag_id`     INT UNSIGNED NOT NULL,
    FOREIGN KEY (`article_id`) REFERENCES `news_articles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`tag_id`)     REFERENCES `news_tags`(`id`)     ON DELETE CASCADE,
    UNIQUE KEY `uq_article_tag` (`article_id`, `tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 59. GALLERY CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_categories` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL,
    `slug`       VARCHAR(100) NOT NULL UNIQUE,
    `project_id` INT UNSIGNED NULL,
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 60. GALLERY IMAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NULL,
    `image_id`    INT UNSIGNED NULL COMMENT 'FK to media_library',
    `thumbnail_media_id` INT UNSIGNED NULL,
    `project_id`   INT UNSIGNED NULL,
    `constituency_id` INT UNSIGNED NULL,
    `title`       VARCHAR(180) NULL,
    `caption`     VARCHAR(255) NULL,
    `alt_text`    VARCHAR(255) NULL,
    `credit`      VARCHAR(180) NULL,
    `taken_at`    DATE NULL,
    `location`    VARCHAR(200) NULL,
    `site_key`    VARCHAR(120) NULL,
    `year`        SMALLINT UNSIGNED NULL,
    `media_type`  ENUM('image','video') NOT NULL DEFAULT 'image',
    `video_url`   VARCHAR(500) NULL,
    `duration`    VARCHAR(30) NULL,
    `external_url` VARCHAR(500) NULL,
    `highlight_summary` TEXT NULL,
    `is_featured` TINYINT(1)   DEFAULT 0,
    `is_highlight` TINYINT(1) NOT NULL DEFAULT 0,
    `status`       ENUM('draft','published','hidden') NOT NULL DEFAULT 'published',
    `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
    `updated_at`  TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 60B. GALLERY MEDIA ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_media` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `gallery_id` INT UNSIGNED NOT NULL,
    `media_id`   INT UNSIGNED NULL,
    `thumbnail_media_id` INT UNSIGNED NULL,
    `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
    `video_url`  VARCHAR(500) NULL,
    `caption`    VARCHAR(255) NULL,
    `alt_text`   VARCHAR(255) NULL,
    `sort_order` SMALLINT UNSIGNED DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`gallery_id`) REFERENCES `gallery_images`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media_library`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`thumbnail_media_id`) REFERENCES `media_library`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 61. FAQ CATEGORIES + ITEMS
-- ============================================================
CREATE TABLE IF NOT EXISTS `faq_categories` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(120) NOT NULL,
    `slug`        VARCHAR(140) NOT NULL UNIQUE,
    `icon`        VARCHAR(80) NULL,
    `description` TEXT NULL,
    `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
    `status`      ENUM('published','draft') NOT NULL DEFAULT 'published',
    `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `faq_items` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id`     INT UNSIGNED NULL,
    `slug`            VARCHAR(180) NULL,
    `question`        TEXT NOT NULL,
    `answer`          LONGTEXT NOT NULL,
    `category`        VARCHAR(100) NULL,
    `sort_order`      SMALLINT UNSIGNED DEFAULT 0,
    `is_popular`      TINYINT(1) NOT NULL DEFAULT 0,
    `status`          ENUM('published','draft') NOT NULL DEFAULT 'published',
    `search_keywords` TEXT NULL,
    `created_by`      INT UNSIGNED NULL,
    `updated_by`      INT UNSIGNED NULL,
    `created_at`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_visible`      TINYINT(1) DEFAULT 1,
    KEY `idx_faq_items_category` (`category_id`),
    KEY `idx_faq_items_status` (`status`),
    FOREIGN KEY (`category_id`) REFERENCES `faq_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 62. LEADERSHIP PROFILES
-- ============================================================
CREATE TABLE IF NOT EXISTS `leadership_profiles` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`             VARCHAR(150) NOT NULL,
    `title`            VARCHAR(150) NOT NULL,
    `organisation`     VARCHAR(200) NULL,
    `photo_id`         INT UNSIGNED NULL,
    `bio`              TEXT NULL,
    `sort_order`       SMALLINT UNSIGNED DEFAULT 0,
    `is_visible`       TINYINT(1)   DEFAULT 1,
    `social_links_json` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 63. STAKEHOLDERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `stakeholders` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `organisation` VARCHAR(200) NOT NULL,
    `role`         VARCHAR(150) NULL,
    `logo_id`      INT UNSIGNED NULL,
    `website`      VARCHAR(255) NULL,
    `sort_order`   SMALLINT UNSIGNED DEFAULT 0,
    `is_visible`   TINYINT(1)   DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 64. CONTACT SUBMISSIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `contact_submissions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150) NOT NULL,
    `email`       VARCHAR(160) NOT NULL,
    `phone`       VARCHAR(30)  NULL,
    `subject`     VARCHAR(200) NULL,
    `attachment_path` VARCHAR(255) NULL,
    `message`     TEXT         NOT NULL,
    `is_read`     TINYINT(1)   DEFAULT 0,
    `status`      ENUM('new','read','replied','archived') NOT NULL DEFAULT 'new',
    `assigned_to` INT UNSIGNED NULL,
    `replied_at`  DATETIME NULL,
    `response_note` TEXT NULL,
    `read_at`     DATETIME NULL,
    `archived_at` DATETIME NULL,
    `ip_address`  VARCHAR(64) NULL,
    `user_agent`  VARCHAR(255) NULL,
    `source_url`  VARCHAR(500) NULL,
    `updated_by`  INT UNSIGNED NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_contact_submissions_status` (`status`, `created_at`),
    KEY `idx_contact_read_status` (`is_read`, `status`),
    KEY `idx_contact_assigned_status` (`assigned_to`, `status`),
    KEY `idx_contact_created` (`created_at`),
    KEY `idx_contact_email` (`email`),
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contact_departments` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`        VARCHAR(150) NOT NULL,
    `subject_key` VARCHAR(80)  NULL,
    `role`        VARCHAR(255) NULL,
    `email`       VARCHAR(150) NULL,
    `phone`       VARCHAR(50)  NULL,
    `icon`        VARCHAR(80)  NULL,
    `accent`      VARCHAR(30)  NULL,
    `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
    `is_visible`  TINYINT(1) DEFAULT 1,
    UNIQUE KEY `uq_contact_department_name` (`name`),
    KEY `idx_contact_departments_visible` (`is_visible`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 65. SUBSCRIBERS
-- ============================================================
CREATE TABLE IF NOT EXISTS `subscribers` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email`         VARCHAR(160) NOT NULL UNIQUE,
    `name`          VARCHAR(150) NULL,
    `status`        ENUM('active','unsubscribed') DEFAULT 'active',
    `subscribed_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `ip`            VARCHAR(45)  NULL,
    `source_url`    VARCHAR(500) NULL,
    `user_agent`    VARCHAR(255) NULL,
    `unsubscribed_at` DATETIME NULL,
    `reactivated_at` DATETIME NULL,
    `updated_by`    INT UNSIGNED NULL,
    `updated_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_subscribers_status` (`status`, `subscribed_at`),
    INDEX `idx_subscribers_email` (`email`),
    INDEX `idx_subscribers_subscribed_at` (`subscribed_at`),
    INDEX `idx_subscribers_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 66. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `type`          VARCHAR(80)  NOT NULL,
    `priority`      ENUM('normal','urgent') DEFAULT 'normal',
    `title`         VARCHAR(255) NOT NULL,
    `body`          TEXT NULL,
    `link`          VARCHAR(500) NULL,
    `source_module` VARCHAR(80) NULL,
    `source_id`     INT UNSIGNED NULL,
    `metadata_json` JSON NULL,
    `is_read`       TINYINT(1)   DEFAULT 0,
    `read_at`       DATETIME NULL,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_notifications_user_created` (`user_id`, `created_at`),
    INDEX `idx_notifications_user_read_created` (`user_id`, `is_read`, `created_at`),
    INDEX `idx_notifications_type_created` (`type`, `created_at`),
    INDEX `idx_notifications_source` (`source_module`, `source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 67. CMS REVISIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_revisions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id`         INT UNSIGNED NULL,
    `section_id`      INT UNSIGNED NULL,
    `revision_type`   VARCHAR(40)  NOT NULL DEFAULT 'page',
    `target_key`      VARCHAR(100) NOT NULL,
    `snapshot_json`   LONGTEXT     NULL,
    `created_by`      INT UNSIGNED NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_page`    (`page_id`),
    INDEX `idx_section` (`section_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 68. MEDIA USAGE
-- ============================================================
CREATE TABLE IF NOT EXISTS `media_usage` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `media_id`        INT UNSIGNED NOT NULL,
    `usage_table`     VARCHAR(80)  NOT NULL,
    `usage_id`        INT UNSIGNED NOT NULL DEFAULT 0,
    `usage_column`    VARCHAR(80)  NULL,
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_media`  (`media_id`),
    INDEX `idx_usage`  (`usage_table`, `usage_id`),
    FOREIGN KEY (`media_id`) REFERENCES `media_library`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 69. REPORT RUNS
-- ============================================================
CREATE TABLE IF NOT EXISTS `report_runs` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NULL,
    `report_type`  VARCHAR(80) NOT NULL,
    `format`       VARCHAR(20) NOT NULL,
    `filters_json` JSON NULL,
    `row_count`    INT UNSIGNED DEFAULT 0,
    `status`       ENUM('generated','failed') DEFAULT 'generated',
    `scope_role`   VARCHAR(40) NULL,
    `scope_user_id` INT UNSIGNED NULL,
    `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_report_runs_user_created` (`user_id`, `created_at`),
    INDEX `idx_report_runs_type_created` (`report_type`, `created_at`),
    INDEX `idx_report_runs_scope` (`scope_role`, `scope_user_id`, `created_at`),
    CONSTRAINT `fk_report_runs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 70. SYSTEM SETTINGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key`    VARCHAR(120) NOT NULL UNIQUE,
    `setting_group`  VARCHAR(80)  NOT NULL,
    `label`          VARCHAR(160) NOT NULL,
    `description`    TEXT NULL,
    `value`          LONGTEXT NULL,
    `default_value`  LONGTEXT NULL,
    `type`           ENUM('text','number','boolean','time','json','select','email','url') DEFAULT 'text',
    `options_json`   JSON NULL,
    `is_sensitive`   TINYINT(1) DEFAULT 0,
    `is_public`      TINYINT(1) DEFAULT 0,
    `sort_order`     INT UNSIGNED DEFAULT 0,
    `updated_by`     INT UNSIGNED NULL,
    `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_system_settings_group_sort` (`setting_group`, `sort_order`),
    INDEX `idx_system_settings_public` (`is_public`),
    INDEX `idx_system_settings_updated` (`updated_at`),
    CONSTRAINT `fk_system_settings_user` FOREIGN KEY (`updated_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 71. SYSTEM SETTING REVISIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_setting_revisions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(120) NOT NULL,
    `old_value`   LONGTEXT NULL,
    `new_value`   LONGTEXT NULL,
    `changed_by`  INT UNSIGNED NULL,
    `changed_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip`          VARCHAR(45) NULL,
    `user_agent`  VARCHAR(255) NULL,
    INDEX `idx_system_setting_revisions_key_changed` (`setting_key`, `changed_at`),
    INDEX `idx_system_setting_revisions_user_changed` (`changed_by`, `changed_at`),
    CONSTRAINT `fk_setting_revisions_user` FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 72. SYSTEM HEALTH SNAPSHOTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `system_health_snapshots` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `status`        ENUM('healthy','warning','critical') DEFAULT 'healthy',
    `score`         TINYINT UNSIGNED DEFAULT 100,
    `checks_json`   JSON NULL,
    `db_json`       JSON NULL,
    `storage_json`  JSON NULL,
    `workflow_json` JSON NULL,
    `security_json` JSON NULL,
    `created_by`    INT UNSIGNED NULL,
    `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_health_status_created` (`status`, `created_at`),
    INDEX `idx_health_created` (`created_at`),
    INDEX `idx_health_created_by` (`created_by`, `created_at`),
    CONSTRAINT `fk_health_snapshots_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 73. EMAIL LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provider`            VARCHAR(40) DEFAULT 'resend',
    `provider_message_id` VARCHAR(120) NULL,
    `recipient_email`     VARCHAR(190) NOT NULL,
    `recipient_user_id`   INT UNSIGNED NULL,
    `subject`             VARCHAR(255) NOT NULL,
    `template_key`        VARCHAR(80) NULL,
    `status`              ENUM('queued','sent','failed','skipped') DEFAULT 'queued',
    `error_message`       TEXT NULL,
    `payload_json`        JSON NULL,
    `sent_at`             DATETIME NULL,
    `created_at`          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email_logs_recipient` (`recipient_email`),
    INDEX `idx_email_logs_user_created` (`recipient_user_id`, `created_at`),
    INDEX `idx_email_logs_status_created` (`status`, `created_at`),
    INDEX `idx_email_logs_provider_message` (`provider_message_id`),
    CONSTRAINT `fk_email_logs_user` FOREIGN KEY (`recipient_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 74. MIGRATIONS TRACKER
-- ============================================================
CREATE TABLE IF NOT EXISTS `_migrations` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(200) NOT NULL UNIQUE,
    `ran_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA — 69 tables + _migrations tracker created
-- Next: Import ahptc_seeds.sql, then run admin/setup.php
-- ============================================================
