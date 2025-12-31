<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class WeightRange
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
            $this->db->query("SELECT 1 FROM weight_ranges LIMIT 1");
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
        // Ensure categories and collections tables exist first
        $categoryModel = new Category();
        $collectionModel = new Collection();
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `weight_ranges` (
          `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `name` VARCHAR(255) NOT NULL,
          `min_weight` DECIMAL(10,2) NOT NULL,
          `max_weight` DECIMAL(10,2) NOT NULL,
          `category_id` INT(11) UNSIGNED DEFAULT NULL,
          `collection_id` INT(11) UNSIGNED DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX `idx_category_id` (`category_id`),
          INDEX `idx_collection_id` (`collection_id`),
          INDEX `idx_weight_range` (`min_weight`, `max_weight`),
          FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
          FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $connection = $this->db->getConnection();
        $connection->exec("SET NAMES utf8mb4");
        $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
        $connection->exec($sql);
        $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    public function getAll($categoryId = null, $collectionId = null)
    {
        $sql = "SELECT wr.*, c.name as category_name, col.name as collection_name 
                FROM weight_ranges wr
                LEFT JOIN categories c ON wr.category_id = c.id
                LEFT JOIN collections col ON wr.collection_id = col.id
                WHERE 1=1";
        
        $params = [];
        
        if ($categoryId !== null) {
            $sql .= " AND (wr.category_id = ? OR wr.category_id IS NULL)";
            $params[] = $categoryId;
        }
        
        if ($collectionId !== null) {
            $sql .= " AND (wr.collection_id = ? OR wr.collection_id IS NULL)";
            $params[] = $collectionId;
        }
        
        $sql .= " ORDER BY wr.min_weight ASC, wr.name ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT wr.*, c.name as category_name, col.name as collection_name 
                FROM weight_ranges wr
                LEFT JOIN categories c ON wr.category_id = c.id
                LEFT JOIN collections col ON wr.collection_id = col.id
                WHERE wr.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function create($data)
    {
        $sql = "INSERT INTO weight_ranges (name, min_weight, max_weight, category_id, collection_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['name'],
            $data['min_weight'],
            $data['max_weight'],
            $data['category_id'] ?? null,
            $data['collection_id'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        foreach (['name', 'min_weight', 'max_weight', 'category_id', 'collection_id'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE weight_ranges SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);
        
        return true;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM weight_ranges WHERE id = ?";
        $this->db->query($sql, [$id]);
    }
}

