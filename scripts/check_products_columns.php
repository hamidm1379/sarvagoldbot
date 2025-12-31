<?php

/**
 * Check if video_file_id and animation_file_id columns exist in products table
 * Usage: php scripts/check_products_columns.php
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

echo "🔍 Checking products table columns...\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}\n\n";

try {
    // Connect to the database
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Get all columns from products table
    $sql = "SHOW COLUMNS FROM `products`";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Columns in products table:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($columns as $column) {
        echo sprintf("%-25s %-20s %s\n", 
            $column['Field'], 
            $column['Type'], 
            $column['Null'] === 'YES' ? 'NULL' : 'NOT NULL'
        );
    }
    echo str_repeat("-", 60) . "\n\n";
    
    // Check for specific columns
    $hasVideo = false;
    $hasAnimation = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'video_file_id') {
            $hasVideo = true;
        }
        if ($column['Field'] === 'animation_file_id') {
            $hasAnimation = true;
        }
    }
    
    if ($hasVideo && $hasAnimation) {
        echo "✅ Both video_file_id and animation_file_id columns exist!\n";
    } else {
        echo "❌ Missing columns:\n";
        if (!$hasVideo) {
            echo "   - video_file_id\n";
        }
        if (!$hasAnimation) {
            echo "   - animation_file_id\n";
        }
        echo "\n💡 Run: php scripts/run_migration_video_animation.php\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

