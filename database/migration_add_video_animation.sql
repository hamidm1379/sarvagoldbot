-- Migration: Add video_file_id and animation_file_id to products table
-- This allows products to have videos and GIFs in addition to images

ALTER TABLE `products` 
ADD COLUMN `video_file_id` VARCHAR(255) DEFAULT NULL AFTER `image_path`,
ADD COLUMN `animation_file_id` VARCHAR(255) DEFAULT NULL AFTER `video_file_id`;

