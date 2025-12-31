<?php

/**
 * Script to test database connection
 * Usage: php scripts/test_db.php
 */

require __DIR__ . '/../index.php';

try {
    $db = \GoldSalekBot\Database::getInstance();
    $connection = $db->getConnection();
    
    echo "✅ Database connection successful!\n\n";
    
    // Test queries
    $tables = ['users', 'products', 'categories', 'collections', 'admins'];
    
    foreach ($tables as $table) {
        $result = $db->fetchOne("SELECT COUNT(*) as count FROM {$table}");
        echo "Table '{$table}': {$result['count']} records\n";
    }
    
    echo "\n✅ All tests passed!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

