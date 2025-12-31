<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Collection
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
            $this->db->query("SELECT 1 FROM collections LIMIT 1");
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
        // Ensure categories table exists first
        $categoryModel = new Category();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `collections` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(255) NOT NULL UNIQUE,
          `category_id` INT(11) UNSIGNED DEFAULT NULL,
          `wage_percentage` DECIMAL(5,2) DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX `idx_category_id` (`category_id`),
          INDEX `idx_wage_percentage` (`wage_percentage`),
          FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $connection = $this->db->getConnection();
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $connection->exec($sql);
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function getAll($categoryId = null)
    {
        if ($categoryId) {
            $sql = "SELECT * FROM collections WHERE category_id = ? ORDER BY name ASC";
            return $this->db->fetchAll($sql, [$categoryId]);
        }
        $sql = "SELECT * FROM collections ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM collections WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function findByName($name)
    {
        $sql = "SELECT * FROM collections WHERE name = ?";
        return $this->db->fetchOne($sql, [$name]);
    }

    public function create($name, $categoryId = null, $wagePercentage = null)
    {
        $sql = "INSERT INTO collections (name, category_id, wage_percentage) VALUES (?, ?, ?)";
        $this->db->query($sql, [$name, $categoryId, $wagePercentage]);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM collections WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function update($id, $name, $categoryId = null, $wagePercentage = null)
    {
        $sql = "UPDATE collections SET name = ?, category_id = ?, wage_percentage = ? WHERE id = ?";
        $this->db->query($sql, [$name, $categoryId, $wagePercentage, $id]);
    }

    public function updateWage($id, $wagePercentage)
    {
        $sql = "UPDATE collections SET wage_percentage = ? WHERE id = ?";
        $this->db->query($sql, [$wagePercentage, $id]);
    }
}

