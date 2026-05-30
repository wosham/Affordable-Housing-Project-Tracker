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
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `token`      VARCHAR(255) NOT NULL UNIQUE,
    `expires_at` DATETIME     NOT NULL,
    `used`       TINYINT(1)   DEFAULT 0,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. AUDIT LOGS
-- ============================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`      INT UNSIGNED NULL,
    `action`       VARCHAR(100) NOT NULL,
    `module`       VARCHAR(80)  NOT NULL,
    `target_id`    INT UNSIGNED DEFAULT 0,
    `details_json` TEXT         NULL,
    `ip`           VARCHAR(45)  NULL,
    `user_agent`   VARCHAR(255) NULL,
    `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user`    (`user_id`),
    INDEX `idx_module`  (`module`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. ANNOUNCEMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `announcements` (
    `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `author_id`         INT UNSIGNED NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `body`              TEXT         NOT NULL,
    `target_roles_json` TEXT         NULL,
    `is_pinned`         TINYINT(1)   DEFAULT 0,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `expires_at`        DATETIME     NULL,
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
    `total_units`    INT UNSIGNED DEFAULT 0,
    `total_projects` INT UNSIGNED DEFAULT 0,
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
    `status`           ENUM('planning','active','stalled','completed') DEFAULT 'planning',
    `pct_complete`     TINYINT UNSIGNED DEFAULT 0,
    `contract_sum`     DECIMAL(15,2) NULL,
    `start_date`       DATE NULL,
    `est_delivery`     DATE NULL,
    `contractor_id`    INT UNSIGNED NULL,
    `consultant_id`    INT UNSIGNED NULL,
    `description`      TEXT NULL,
    `hero_image`       VARCHAR(255) NULL,
    `images_json`      TEXT NULL,
    `funding_source`   VARCHAR(150) NULL,
    `lead_agency`      VARCHAR(150) NULL,
    `units`            INT UNSIGNED NULL,
    `is_featured`      TINYINT(1)   DEFAULT 0,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`)     REFERENCES `project_categories`(`id`),
    FOREIGN KEY (`constituency_id`) REFERENCES `constituencies`(`id`),
    INDEX `idx_status`   (`status`),
    INDEX `idx_featured` (`is_featured`)
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
    `assigned_by` INT UNSIGNED NOT NULL,
    `assigned_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    UNIQUE KEY `uq_project_user` (`project_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. MILESTONES
-- ============================================================
CREATE TABLE IF NOT EXISTS `milestones` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id`  INT UNSIGNED NOT NULL,
    `label`       VARCHAR(200) NOT NULL,
    `target_date` DATE NULL,
    `actual_date` DATE NULL,
    `status`      ENUM('pending','current','done') DEFAULT 'pending',
    `sequence`    SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
    INDEX `idx_project_seq` (`project_id`, `sequence`)
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
    `created_at`     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`)
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
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
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
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
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
    FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`)
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
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    FOREIGN KEY (`tested_by`)  REFERENCES `users`(`id`)
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
    FOREIGN KEY (`inspected_by`) REFERENCES `users`(`id`)
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
    FOREIGN KEY (`closed_by`)  REFERENCES `users`(`id`)
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
    FOREIGN KEY (`reviewed_by`)  REFERENCES `users`(`id`)
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
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)
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
    `status`           ENUM('draft','submitted','clerk-endorsed','certified','approved','paid') DEFAULT 'draft',
    `submitted_at`     DATETIME NULL,
    `certified_at`     DATETIME NULL,
    `approved_at`      DATETIME NULL,
    `paid_at`          DATETIME NULL,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`project_id`)    REFERENCES `projects`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`contractor_id`) REFERENCES `users`(`id`),
    UNIQUE KEY `uq_project_ipc_no` (`project_id`, `ipc_number`)
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
    FOREIGN KEY (`boq_item_id`) REFERENCES `boq_items`(`id`) ON DELETE SET NULL
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
    FOREIGN KEY (`action_by`) REFERENCES `users`(`id`)
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
    FOREIGN KEY (`approved_by`)  REFERENCES `users`(`id`)
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
    `created_at`          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    UNIQUE KEY `uq_project_date` (`project_id`, `date`)
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
    UNIQUE KEY `uq_user_date` (`user_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 45. MESSAGE THREADS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_threads` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `subject`    VARCHAR(255) NOT NULL,
    `type`       ENUM('direct','group','project-channel') DEFAULT 'direct',
    `project_id` INT UNSIGNED NULL,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
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
    `body`       TEXT         NOT NULL,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `edited_at`  DATETIME NULL,
    `is_deleted` TINYINT(1)   DEFAULT 0,
    FOREIGN KEY (`thread_id`) REFERENCES `message_threads`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 47. MESSAGE PARTICIPANTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_participants` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `thread_id` INT UNSIGNED NOT NULL,
    `user_id`   INT UNSIGNED NOT NULL,
    `joined_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `is_admin`  TINYINT(1)   DEFAULT 0,
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
    FOREIGN KEY (`message_id`) REFERENCES `messages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
    UNIQUE KEY `uq_msg_user` (`message_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 49. MESSAGE ATTACHMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS `message_attachments` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `message_id`    INT UNSIGNED NOT NULL,
    `filename`      VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `size`          INT UNSIGNED NOT NULL,
    `type`          VARCHAR(100) NOT NULL,
    `path`          VARCHAR(255) NOT NULL,
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
    `category_id`       INT UNSIGNED NULL,
    `author_id`         INT UNSIGNED NOT NULL,
    `title`             VARCHAR(255) NOT NULL,
    `slug`              VARCHAR(255) NOT NULL UNIQUE,
    `excerpt`           TEXT NULL,
    `body`              LONGTEXT     NOT NULL,
    `featured_image_id` INT UNSIGNED NULL,
    `status`            ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
    `published_at`      DATETIME NULL,
    `scheduled_for`     DATETIME NULL,
    `views`             INT UNSIGNED DEFAULT 0,
    `seo_title`         VARCHAR(255) NULL,
    `seo_description`   TEXT NULL,
    `og_image_id`       INT UNSIGNED NULL,
    `created_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `news_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`author_id`)   REFERENCES `users`(`id`),
    INDEX `idx_status`    (`status`),
    INDEX `idx_published` (`published_at`)
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
    FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 60. GALLERY IMAGES
-- ============================================================
CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT UNSIGNED NULL,
    `image_id`    INT UNSIGNED NULL COMMENT 'FK to media_library',
    `caption`     VARCHAR(255) NULL,
    `taken_at`    DATE NULL,
    `location`    VARCHAR(200) NULL,
    `is_featured` TINYINT(1)   DEFAULT 0,
    `sort_order`  SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (`category_id`) REFERENCES `gallery_categories`(`id`) ON DELETE SET NULL
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
    `message`     TEXT         NOT NULL,
    `is_read`     TINYINT(1)   DEFAULT 0,
    `assigned_to` INT UNSIGNED NULL,
    `replied_at`  DATETIME NULL,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL
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
    `ip`            VARCHAR(45)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 66. NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       VARCHAR(80)  NOT NULL,
    `title`      VARCHAR(255) NOT NULL,
    `body`       TEXT NULL,
    `link`       VARCHAR(500) NULL,
    `is_read`    TINYINT(1)   DEFAULT 0,
    `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`)
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
-- 69. MIGRATIONS TRACKER
-- ============================================================
CREATE TABLE IF NOT EXISTS `_migrations` (
    `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(200) NOT NULL UNIQUE,
    `ran_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF SCHEMA — 68 tables + _migrations tracker created
-- Next: Import ahptc_seeds.sql, then run admin/setup.php
-- ============================================================
