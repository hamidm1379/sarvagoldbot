<?php

/**
 * Fix Products Table - Add missing 'name' column
 * 
 * This script adds the 'name' column to the products table if it doesn't exist.
 * Run this if you're getting "Unknown column 'name' in 'field list'" error.
 * 
 * Usage: php scripts/fix_products_name_column.php
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!empty($name)) {
            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'gold_salek_bot';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

echo "🔧 Fixing products table...\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}\n\n";

try {
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'name'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "✅ Column 'name' already exists in products table.\n";
    } else {
        echo "⚠️  Column 'name' not found. Adding it...\n";
        
        // Add the name column after product_code
        $sql = "ALTER TABLE products 
                ADD COLUMN `name` VARCHAR(255) NOT NULL AFTER `product_code`";
        
        $pdo->exec($sql);
        echo "✅ Column 'name' added successfully!\n";
    }
    
    // Verify the column
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "\n📋 Current columns in products table:\n";
    foreach ($columns as $col) {
        echo "   - {$col}\n";
    }
    
    echo "\n🎉 Done!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

