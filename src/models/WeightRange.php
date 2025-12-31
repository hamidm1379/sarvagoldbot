<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class WeightRange
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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

