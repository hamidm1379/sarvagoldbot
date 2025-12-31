-- Add Admin to Gold Salek Bot
-- Usage: mysql -u username -p database_name < add_admin.sql
-- or: mariadb -u username -p database_name < add_admin.sql

INSERT INTO `admins` (`telegram_id`, `username`) 
VALUES (8504577397, 'admin')
ON DUPLICATE KEY UPDATE `username`='admin';

INSERT INTO `admins` (`telegram_id`, `username`) 
VALUES (43273891, 'admin2')
ON DUPLICATE KEY UPDATE `username`='admin2';

