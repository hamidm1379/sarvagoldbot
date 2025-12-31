<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Admin
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function isAdmin($telegramId)
    {
        $sql = "SELECT * FROM admins WHERE telegram_id = ?";
        $admin = $this->db->fetchOne($sql, [$telegramId]);
        return $admin !== false;
    }

    public function addAdmin($telegramId, $username = null)
    {
        $sql = "INSERT INTO admins (telegram_id, username) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE username = ?";
        $this->db->query($sql, [$telegramId, $username, $username]);
    }

    public function removeAdmin($telegramId)
    {
        $sql = "DELETE FROM admins WHERE telegram_id = ?";
        $this->db->query($sql, [$telegramId]);
    }
}

