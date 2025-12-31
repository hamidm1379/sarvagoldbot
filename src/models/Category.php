<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Category
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
            $this->db->query("SELECT 1 FROM categories LIMIT 1");
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
            CREATE TABLE IF NOT EXISTS `categories` (
              `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL UNIQUE,
              `sort_order` INT(11) DEFAULT 0,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $connection = $this->db->getConnection();
            $connection->exec("SET NAMES utf8mb4");
            $connection->exec("SET FOREIGN_KEY_CHECKS = 0");
            $connection->exec($sql);
            $connection->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (\PDOException $e) {
            error_log("Failed to create categories table: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAll()
    {
        $sql = "SELECT * FROM categories ORDER BY sort_order ASC, name ASC";
        return $this->db->fetchAll($sql);
    }

    public function findById($id)
    {
        $sql = "SELECT * FROM categories WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function findByName($name)
    {
        $sql = "SELECT * FROM categories WHERE name = ?";
        return $this->db->fetchOne($sql, [$name]);
    }

    public function create($name, $sortOrder = 0)
    {
        $sql = "INSERT INTO categories (name, sort_order) VALUES (?, ?)";
        $this->db->query($sql, [$name, $sortOrder]);
        return $this->db->lastInsertId();
    }

    public function delete($id)
    {
        $sql = "DELETE FROM categories WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function update($id, $name, $sortOrder = null)
    {
        if ($sortOrder !== null) {
            $sql = "UPDATE categories SET name = ?, sort_order = ? WHERE id = ?";
            $this->db->query($sql, [$name, $sortOrder, $id]);
        } else {
            $sql = "UPDATE categories SET name = ? WHERE id = ?";
            $this->db->query($sql, [$name, $id]);
        }
    }
}

