<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class WageRange
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($categoryId = null, $collectionId = null)
    {
        $sql = "SELECT wr.*, c.name as category_name, col.name as collection_name 
                FROM wage_ranges wr
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

        $sql .= " ORDER BY wr.min_wage ASC, wr.name ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findById($id)
    {
        $sql = "SELECT wr.*, c.name as category_name, col.name as collection_name 
                FROM wage_ranges wr
                LEFT JOIN categories c ON wr.category_id = c.id
                LEFT JOIN collections col ON wr.collection_id = col.id
                WHERE wr.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function create($data)
    {
        $sql = "INSERT INTO wage_ranges (name, min_wage, max_wage, category_id, collection_id) 
                VALUES (?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $data['name'],
            $data['min_wage'],
            $data['max_wage'],
            $data['category_id'] ?? null,
            $data['collection_id'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];

        foreach (['name', 'min_wage', 'max_wage', 'category_id', 'collection_id'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE wage_ranges SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);

        return true;
    }

    public function delete($id)
    {
        $sql = "DELETE FROM wage_ranges WHERE id = ?";
        $this->db->query($sql, [$id]);
    }
}


