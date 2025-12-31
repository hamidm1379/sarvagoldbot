<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Product
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findByCode($code)
    {
        $sql = "SELECT p.*, c.name as category_name, col.name as collection_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN collections col ON p.collection_id = col.id
                WHERE p.product_code = ? AND p.status = 'active'";
        return $this->db->fetchOne($sql, [$code]);
    }

    public function findByCodeForAdmin($code)
    {
        $sql = "SELECT p.*, c.name as category_name, col.name as collection_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN collections col ON p.collection_id = col.id
                WHERE p.product_code = ?";
        return $this->db->fetchOne($sql, [$code]);
    }

    public function getAll($filters = [])
    {
        $sql = "SELECT p.*, c.name as category_name, col.name as collection_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN collections col ON p.collection_id = col.id
                WHERE p.status = 'active'";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['collection_id'])) {
            $sql .= " AND p.collection_id = ?";
            $params[] = $filters['collection_id'];
        }
        
        if (!empty($filters['weight'])) {
            $sql .= " AND p.weight = ?";
            $params[] = $filters['weight'];
        }
        
        if (!empty($filters['weight_min']) && !empty($filters['weight_max'])) {
            $sql .= " AND p.weight >= ? AND p.weight <= ?";
            $params[] = $filters['weight_min'];
            $params[] = $filters['weight_max'];
        } elseif (!empty($filters['weight_min'])) {
            $sql .= " AND p.weight >= ?";
            $params[] = $filters['weight_min'];
        } elseif (!empty($filters['weight_max'])) {
            $sql .= " AND p.weight <= ?";
            $params[] = $filters['weight_max'];
        }
        
        if (!empty($filters['wage_percentage'])) {
            $sql .= " AND p.wage_percentage = ?";
            $params[] = $filters['wage_percentage'];
        }

        if (!empty($filters['wage_min']) && !empty($filters['wage_max'])) {
            $sql .= " AND p.wage_percentage >= ? AND p.wage_percentage <= ?";
            $params[] = $filters['wage_min'];
            $params[] = $filters['wage_max'];
        } elseif (!empty($filters['wage_min'])) {
            $sql .= " AND p.wage_percentage >= ?";
            $params[] = $filters['wage_min'];
        } elseif (!empty($filters['wage_max'])) {
            $sql .= " AND p.wage_percentage <= ?";
            $params[] = $filters['wage_max'];
        }
        
        $sql .= " ORDER BY p.id DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    public function getPaginated($filters = [], $limit = 10, $offset = 0)
    {
        $sql = "SELECT p.*, c.name as category_name, col.name as collection_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN collections col ON p.collection_id = col.id
                WHERE p.status = 'active'";
        
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['collection_id'])) {
            $sql .= " AND p.collection_id = ?";
            $params[] = $filters['collection_id'];
        }
        
        if (!empty($filters['weight'])) {
            $sql .= " AND p.weight = ?";
            $params[] = $filters['weight'];
        }
        
        if (!empty($filters['weight_min']) && !empty($filters['weight_max'])) {
            $sql .= " AND p.weight >= ? AND p.weight <= ?";
            $params[] = $filters['weight_min'];
            $params[] = $filters['weight_max'];
        } elseif (!empty($filters['weight_min'])) {
            $sql .= " AND p.weight >= ?";
            $params[] = $filters['weight_min'];
        } elseif (!empty($filters['weight_max'])) {
            $sql .= " AND p.weight <= ?";
            $params[] = $filters['weight_max'];
        }
        
        if (!empty($filters['wage_percentage'])) {
            $sql .= " AND p.wage_percentage = ?";
            $params[] = $filters['wage_percentage'];
        }
        
        $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    public function count($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
        $params = [];
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['collection_id'])) {
            $sql .= " AND collection_id = ?";
            $params[] = $filters['collection_id'];
        }
        
        if (!empty($filters['weight'])) {
            $sql .= " AND weight = ?";
            $params[] = $filters['weight'];
        }
        
        if (!empty($filters['weight_min']) && !empty($filters['weight_max'])) {
            $sql .= " AND weight >= ? AND weight <= ?";
            $params[] = $filters['weight_min'];
            $params[] = $filters['weight_max'];
        } elseif (!empty($filters['weight_min'])) {
            $sql .= " AND weight >= ?";
            $params[] = $filters['weight_min'];
        } elseif (!empty($filters['weight_max'])) {
            $sql .= " AND weight <= ?";
            $params[] = $filters['weight_max'];
        }
        
        if (!empty($filters['wage_percentage'])) {
            $sql .= " AND wage_percentage = ?";
            $params[] = $filters['wage_percentage'];
        }

        if (!empty($filters['wage_min']) && !empty($filters['wage_max'])) {
            $sql .= " AND wage_percentage >= ? AND wage_percentage <= ?";
            $params[] = $filters['wage_min'];
            $params[] = $filters['wage_max'];
        } elseif (!empty($filters['wage_min'])) {
            $sql .= " AND wage_percentage >= ?";
            $params[] = $filters['wage_min'];
        } elseif (!empty($filters['wage_max'])) {
            $sql .= " AND wage_percentage <= ?";
            $params[] = $filters['wage_max'];
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }

    public function create($data)
    {
        $sql = "INSERT INTO products (product_code, name, image_file_id, image_path, video_file_id, animation_file_id, category_id, collection_id, wage_percentage, weight, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->query($sql, [
            $data['product_code'],
            $data['name'],
            $data['image_file_id'] ?? null,
            $data['image_path'] ?? null,
            $data['video_file_id'] ?? null,
            $data['animation_file_id'] ?? null,
            $data['category_id'],
            $data['collection_id'] ?? null,
            $data['wage_percentage'],
            $data['weight'],
            $data['status'] ?? 'active'
        ]);
        
        return $this->db->lastInsertId();
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        
        foreach (['product_code', 'name', 'image_file_id', 'image_path', 'video_file_id', 'animation_file_id', 'category_id', 'collection_id', 'wage_percentage', 'weight', 'status'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
        $this->db->query($sql, $params);
        
        return true;
    }

    public function delete($id)
    {
        $sql = "UPDATE products SET status = 'inactive' WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function deletePermanently($id)
    {
        $sql = "DELETE FROM products WHERE id = ?";
        $this->db->query($sql, [$id]);
    }

    public function generateUniqueCode()
    {
        do {
            $code = rand(1000, 9999);
            $exists = $this->findByCodeForAdmin($code);
        } while ($exists);

        return $code;
    }

    public function findById($id)
    {
        $sql = "SELECT p.*, c.name as category_name, col.name as collection_name 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN collections col ON p.collection_id = col.id
                WHERE p.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    public function getDistinctWeights()
    {
        $sql = "SELECT DISTINCT weight FROM products WHERE status = 'active' ORDER BY weight ASC";
        return $this->db->fetchAll($sql);
    }

    public function getDistinctWagePercentages()
    {
        $sql = "SELECT DISTINCT wage_percentage FROM products WHERE status = 'active' ORDER BY wage_percentage ASC";
        return $this->db->fetchAll($sql);
    }
}

