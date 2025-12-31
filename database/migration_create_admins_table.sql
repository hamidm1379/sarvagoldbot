-- Migration: Create admins table
-- This migration creates the admins table if it doesn't exist
-- Usage: mysql -u username -p database_name < database/migration_create_admins_table.sql
-- or: mariadb -u username -p database_name < database/migration_create_admins_table.sql

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
  `username` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_telegram_id` (`telegram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

