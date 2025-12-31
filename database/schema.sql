-- Gold Salek Telegram Bot Database Schema
-- Persian (RTL) Support with UTF8MB4
-- Compatible with MariaDB 10.3+ and MySQL 5.7+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `internal_id` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_telegram_id` (`telegram_id`),
  INDEX `idx_internal_id` (`internal_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `telegram_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
  `username` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_telegram_id` (`telegram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `sort_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collections Table
CREATE TABLE IF NOT EXISTS `collections` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `wage_percentage` DECIMAL(5,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_wage_percentage` (`wage_percentage`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_code` INT(11) UNSIGNED NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `image_file_id` VARCHAR(255) DEFAULT NULL,
  `image_path` VARCHAR(500) DEFAULT NULL,
  `category_id` INT(11) UNSIGNED NOT NULL,
  `collection_id` INT(11) UNSIGNED DEFAULT NULL,
  `wage_percentage` DECIMAL(5,2) NOT NULL,
  `weight` DECIMAL(10,2) NOT NULL,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_code` (`product_code`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_collection_id` (`collection_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_wage_percentage` (`wage_percentage`),
  INDEX `idx_weight` (`weight`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Wage Ranges Table
CREATE TABLE IF NOT EXISTS `wage_ranges` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `min_wage` DECIMAL(6,2) NOT NULL,
  `max_wage` DECIMAL(6,2) NOT NULL,
  `category_id` INT(11) UNSIGNED DEFAULT NULL,
  `collection_id` INT(11) UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_collection_id` (`collection_id`),
  INDEX `idx_wage_range` (`min_wage`, `max_wage`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Categories
INSERT INTO `categories` (`name`, `sort_order`) VALUES
('انگشتر', 1),
('دستبند', 2),
('سرویس', 3),
('گوشواره', 4),
('نیم ست', 5),
('زنجیر', 6)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Insert Default Admins
INSERT INTO `admins` (`telegram_id`, `username`) VALUES (8504577397, 'admin')
ON DUPLICATE KEY UPDATE `username`='admin';

INSERT INTO `admins` (`telegram_id`, `username`) VALUES (43273891, 'admin2')
ON DUPLICATE KEY UPDATE `username`='admin2';

SET FOREIGN_KEY_CHECKS = 1;

