<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

