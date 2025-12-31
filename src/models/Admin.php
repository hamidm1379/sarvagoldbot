<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Admin
{
    private $db;
    private static $tableCreated = false;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        if (self::$tableCreated) {
            return;
        }

        try {
            // Try to query the table to see if it exists
            $this->db->query("SELECT 1 FROM admins LIMIT 1");
            self::$tableCreated = true;
        } catch (\PDOException $e) {
            // Table doesn't exist, create it
            if (strpos($e->getMessage(), "doesn't exist") !== false || 
                strpos($e->getMessage(), 'Base table or view not found') !== false) {
                $this->createTable();
                self::$tableCreated = true;
            } else {
                throw $e;
            }
        }
    }

    private function createTable()
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `telegram_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
          `username` VARCHAR(255) DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX `idx_telegram_id` (`telegram_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $connection = $this->db->getConnection();
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $connection->exec($sql);
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function isAdmin($telegramId)
    {
        $this->ensureTableExists();
        $sql = "SELECT * FROM admins WHERE telegram_id = ?";
        $admin = $this->db->fetchOne($sql, [$telegramId]);
        return $admin !== false;
    }

    public function addAdmin($telegramId, $username = null)
    {
        $this->ensureTableExists();
        $sql = "INSERT INTO admins (telegram_id, username) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE username = ?";
        $this->db->query($sql, [$telegramId, $username, $username]);
    }

    public function removeAdmin($telegramId)
    {
        $this->ensureTableExists();
        $sql = "DELETE FROM admins WHERE telegram_id = ?";
        $this->db->query($sql, [$telegramId]);
    }
}

