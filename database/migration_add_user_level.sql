-- Migration: Add user level column
-- This migration adds a level column to the users table for user tier management

ALTER TABLE `users` 
ADD COLUMN `level` ENUM('general', 'vip', 'level1', 'level2', 'level3', 'level4') 
DEFAULT 'general' 
AFTER `status`;

-- Add index for level column
ALTER TABLE `users` 
ADD INDEX `idx_level` (`level`);

-- Update existing users to have 'general' level if NULL
UPDATE `users` SET `level` = 'general' WHERE `level` IS NULL;

