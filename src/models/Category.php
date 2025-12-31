<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Category
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

