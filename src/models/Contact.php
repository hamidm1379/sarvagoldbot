<?php

namespace GoldSalekBot\Models;

use GoldSalekBot\Database;

class Contact
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
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
