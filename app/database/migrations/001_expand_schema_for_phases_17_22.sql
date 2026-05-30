-- ============================================================
-- Migration 001: Expand schema for Phases 17-22
-- Adds missing columns and tables required by admin pages,
-- models, and API endpoints built in Phases 17-22.
-- Safe to run multiple times (idempotent where possible).
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

-- ============================================================
-- 1. USERS - add avatar, job_title, department, is_public
-- ============================================================
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(255) NULL AFTER `password_hash`,
  ADD COLUMN IF NOT EXISTS `job_title` VARCHAR(100) NULL AFTER `avatar`,
  ADD COLUMN IF NOT EXISTS `department` VARCHAR(100) NULL AFTER `job_title`,
  ADD COLUMN IF NOT EXISTS `is_public` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- ============================================================
-- 2. GEO_FENCES - add status, verified_by, verified_at, notes
-- ============================================================
ALTER TABLE `geo_fences`
  ADD COLUMN IF NOT EXISTS `status` VARCHAR(40) NOT NULL DEFAULT 'configured' AFTER `radius_meters`,
  ADD COLUMN IF NOT EXISTS `verified_by` INT UNSIGNED NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `verified_at` DATETIME NULL AFTER `verified_by`,
  ADD COLUMN IF NOT EXISTS `notes` TEXT NULL AFTER `verified_at`;

-- ============================================================
-- 3. CMS_PAGES - add template, route_path, hero_image
-- ============================================================
ALTER TABLE `cms_pages`
  ADD COLUMN IF NOT EXISTS `template` VARCHAR(60) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `route_path` VARCHAR(200) NULL AFTER `template`,
  ADD COLUMN IF NOT EXISTS `hero_image` VARCHAR(255) NULL AFTER `canonical_url`;

-- ============================================================
-- 4. CMS_SECTIONS - add section_type, sort_order, editor_mode, is_locked
-- ============================================================
ALTER TABLE `cms_sections`
  ADD COLUMN IF NOT EXISTS `section_type` VARCHAR(40) NOT NULL DEFAULT 'rich_text' AFTER `label`,
  ADD COLUMN IF NOT EXISTS `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `section_type`,
  ADD COLUMN IF NOT EXISTS `editor_mode` VARCHAR(40) NOT NULL DEFAULT 'structured' AFTER `sort_order`,
  ADD COLUMN IF NOT EXISTS `is_locked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_visible`;

-- ============================================================
-- 5. MEDIA_LIBRARY - add title, width, height, extension, source, caption
-- ============================================================
ALTER TABLE `media_library`
  ADD COLUMN IF NOT EXISTS `title` VARCHAR(255) NULL AFTER `original_name`,
  ADD COLUMN IF NOT EXISTS `width` INT UNSIGNED NULL AFTER `size`,
  ADD COLUMN IF NOT EXISTS `height` INT UNSIGNED NULL AFTER `width`,
  ADD COLUMN IF NOT EXISTS `extension` VARCHAR(20) NULL AFTER `height`,
  ADD COLUMN IF NOT EXISTS `source` VARCHAR(60) NOT NULL DEFAULT 'upload' AFTER `folder`,
  ADD COLUMN IF NOT EXISTS `caption` VARCHAR(500) NULL AFTER `alt_text`;

-- ============================================================
-- 6. NEWS_ARTICLES - add slug
-- ============================================================
ALTER TABLE `news_articles`
  ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) NOT NULL AFTER `title`;

-- Add unique index on slug if it doesn't exist
SET @index_exists = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'news_articles'
    AND INDEX_NAME = 'uq_news_slug'
);
SET @sql = IF(@index_exists = 0,
  'ALTER TABLE `news_articles` ADD UNIQUE INDEX `uq_news_slug` (`slug`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- 7. CMS_REVISIONS - new table for CMS edit history
-- ============================================================
CREATE TABLE IF NOT EXISTS `cms_revisions` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_id`         INT UNSIGNED NULL,
    `section_id`      INT UNSIGNED NULL,
    `revision_type`   VARCHAR(40) NOT NULL DEFAULT 'page',
    `target_key`      VARCHAR(100) NOT NULL,
    `snapshot_json`   LONGTEXT NULL,
    `created_by`      INT UNSIGNED NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_page` (`page_id`),
    INDEX `idx_section` (`section_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. MEDIA_USAGE - new table for media usage tracking
-- ============================================================
CREATE TABLE IF NOT EXISTS `media_usage` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `media_id`        INT UNSIGNED NOT NULL,
    `usage_table`     VARCHAR(80) NOT NULL,
    `usage_id`        INT UNSIGNED NOT NULL DEFAULT 0,
    `usage_column`    VARCHAR(80) NULL,
    `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_media` (`media_id`),
    INDEX `idx_usage` (`usage_table`, `usage_id`),
    FOREIGN KEY (`media_id`) REFERENCES `media_library`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
