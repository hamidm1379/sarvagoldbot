<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class User
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
            $this->db->query("SELECT 1 FROM users LIMIT 1");
            self::$tableCreated = true;
        } catch (\PDOException $e) {
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
        try {
            $sql = "
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `telegram_id` BIGINT(20) UNSIGNED NOT NULL UNIQUE,
              `first_name` VARCHAR(255) NOT NULL,
              `last_name` VARCHAR(255) NOT NULL,
              `internal_id` VARCHAR(50) NOT NULL UNIQUE,
              `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
              `level` ENUM('general', 'vip', 'level1', 'level2', 'level3', 'level4') DEFAULT 'general',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_telegram_id` (`telegram_id`),
              INDEX `idx_internal_id` (`internal_id`),
              INDEX `idx_status` (`status`),
              INDEX `idx_level` (`level`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $connection = $this->db->getConnection();
            $connection->exec("SET NAMES utf8mb4");
            $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
            $connection->exec($sql);
            $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\PDOException $e) {
            error_log("Failed to create users table: " . $e->getMessage());
            throw $e;
        }
    }

    public function findByTelegramId($telegramId)
    {
        $sql = "SELECT * FROM users WHERE telegram_id = ?";
        return $this->db->fetchOne($sql, [$telegramId]);
    }

    public function findByInternalId($internalId)
    {
        $sql = "SELECT * FROM users WHERE internal_id = ?";
        return $this->db->fetchOne($sql, [$internalId]);
    }

    public function create($telegramId, $firstName, $lastName)
    {
        $internalId = $this->generateInternalId();
        
        $sql = "INSERT INTO users (telegram_id, first_name, last_name, internal_id, status) 
                VALUES (?, ?, ?, ?, 'approved')";
        
        $this->db->query($sql, [$telegramId, $firstName, $lastName, $internalId]);
        
        return $this->findByTelegramId($telegramId);
    }

    public function updateStatus($telegramId, $status)
    {
        $sql = "UPDATE users SET status = ? WHERE telegram_id = ?";
        $this->db->query($sql, [$status, $telegramId]);
    }

    public function getAllPending()
    {
        $sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function getAllApproved()
    {
        $sql = "SELECT * FROM users WHERE status = 'approved' ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function delete($telegramId)
    {
        $sql = "DELETE FROM users WHERE telegram_id = ?";
        $this->db->query($sql, [$telegramId]);
    }

    public function updateLevel($telegramId, $level)
    {
        $sql = "UPDATE users SET level = ? WHERE telegram_id = ?";
        $this->db->query($sql, [$level, $telegramId]);
    }

    public function getLevel($telegramId)
    {
        $user = $this->findByTelegramId($telegramId);
        return $user ? ($user['level'] ?? 'general') : 'general';
    }

    /**
     * Calculate display wage based on user level
     * @param float $wage Original wage percentage
     * @param string $level User level
     * @return float|null Returns calculated wage or null if user shouldn't see it
     */
    public static function calculateDisplayWage($wage, $level)
    {
        if ($wage === null) {
            return null;
        }

        switch ($level) {
            case 'general':
                return null; // General users don't see wage
            case 'vip':
                return $wage; // VIP users see original wage
            case 'level1':
                return $wage + 1;
            case 'level2':
                return $wage + 2;
            case 'level3':
                return $wage + 3;
            case 'level4':
                return $wage + 4;
            default:
                return null;
        }
    }

    /**
     * Search users by name, telegram_id, or internal_id
     * @param string $query Search query
     * @return array Array of matching users
     */
    public function search($query)
    {
        $searchTerm = '%' . $query . '%';
        $sql = "SELECT * FROM users 
                WHERE first_name LIKE ? 
                   OR last_name LIKE ? 
                   OR CONCAT(first_name, ' ', last_name) LIKE ?
                   OR telegram_id LIKE ?
                   OR internal_id LIKE ?
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    private function generateInternalId()
    {
        do {
            $id = 'USER-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->findByInternalId($id);
        } while ($exists);

        return $id;
    }
}

