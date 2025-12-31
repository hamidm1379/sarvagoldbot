-- Migration: Add weight_ranges table
-- Run this SQL script to create the weight_ranges table

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Weight Ranges Table
CREATE TABLE IF NOT EXISTS `weight_ranges` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `min_weight` DECIMAL(10,2) NOT NULL,
  `max_weight` DECIMAL(10,2) NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `collection_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_collection_id` (`collection_id`),
  INDEX `idx_weight_range` (`min_weight`, `max_weight`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

