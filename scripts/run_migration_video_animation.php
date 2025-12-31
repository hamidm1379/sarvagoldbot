<?php

/**
 * Run migration: Add video_file_id and animation_file_id to products table
 * Usage: php scripts/run_migration_video_animation.php
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

echo "📊 Running migration: Add video_file_id and animation_file_id to products table\n";
echo "Database: {$dbname}\n";
echo "Host: {$host}\n\n";

try {
    // Connect to the database
    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    // Check if columns already exist
    $checkSql = "SHOW COLUMNS FROM `products` LIKE 'video_file_id'";
    $stmt = $pdo->query($checkSql);
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'video_file_id' already exists in 'products' table.\n";
        
        $checkSql2 = "SHOW COLUMNS FROM `products` LIKE 'animation_file_id'";
        $stmt2 = $pdo->query($checkSql2);
        if ($stmt2->rowCount() > 0) {
            echo "✅ Column 'animation_file_id' already exists in 'products' table.\n";
            echo "   Migration already applied.\n";
            exit(0);
        }
    }
    
    // Read migration file
    $migrationFile = __DIR__ . '/../database/migration_add_video_animation.sql';
    if (!file_exists($migrationFile)) {
        die("❌ Migration file not found: {$migrationFile}\n");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute migration
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        if (stripos($statement, 'SET NAMES') !== false || 
            stripos($statement, 'SET FOREIGN_KEY') !== false) {
            continue; // Already executed
        }
        
        try {
            echo "🔧 Executing: " . substr($statement, 0, 100) . "...\n";
            $pdo->exec($statement);
            echo "✅ Successfully executed!\n";
        } catch (PDOException $e) {
            // Check if it's "duplicate column" error
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "⚠️  Column already exists (ignoring)\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
                throw $e;
            }
        }
    }
    
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ Migration completed successfully!\n";
    echo "   Products table now supports video and animation (GIF) file IDs.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

