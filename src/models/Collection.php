<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Collection
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

