-- Migration: Add wage_percentage to collections table
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `collections` 
ADD COLUMN `wage_percentage` DECIMAL(5,2) DEFAULT NULL AFTER `category_id`,
ADD INDEX `idx_wage_percentage` (`wage_percentage`);

SET FOREIGN_KEY_CHECKS = 1;











