<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Contact
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
            $this->db->query("SELECT 1 FROM contact_info LIMIT 1");
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
        $sql = "
        CREATE TABLE IF NOT EXISTS `contact_info` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `address` TEXT NOT NULL,
          `phone` VARCHAR(50) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $connection = $this->db->getConnection();
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $connection->exec($sql);
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function get()
    {
        $sql = "SELECT * FROM contact_info LIMIT 1";
        $contact = $this->db->fetchOne($sql);
        
        // If no contact info exists, return default values
        if (!$contact) {
            return [
                'address' => 'بازار بزرگ تهران پاساژ طلا و جواهر خرداد طبقه همکف پلاک 68',
                'phone' => '02155612268'
            ];
        }
        
        return $contact;
    }

    public function update($address, $phone)
    {
        // Check if contact info exists
        $existing = $this->db->fetchOne("SELECT id FROM contact_info LIMIT 1");
        
        if ($existing) {
            // Update existing record
            $sql = "UPDATE contact_info SET address = ?, phone = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$address, $phone, $existing['id']]);
        } else {
            // Insert new record
            $sql = "INSERT INTO contact_info (address, phone) VALUES (?, ?)";
            $this->db->query($sql, [$address, $phone]);
        }
    }
}
