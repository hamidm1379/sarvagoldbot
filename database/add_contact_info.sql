-- Contact Info Table
CREATE TABLE IF NOT EXISTS `contact_info` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default contact info (only if table is empty)
INSERT INTO `contact_info` (`address`, `phone`) 
SELECT 'بازار بزرگ تهران پاساژ طلا و جواهر خرداد طبقه همکف پلاک 68', '02155612268'
WHERE NOT EXISTS (SELECT 1 FROM `contact_info`);
